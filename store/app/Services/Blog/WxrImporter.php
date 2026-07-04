<?php

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogMedia;
use App\Models\BlogTag;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

/**
 * Imports a WordPress WXR (eXtended RSS) export into the blog module.
 *
 * Design notes (see product-development/features/blog/PRD.md §10):
 *  - Idempotent: posts keyed on wp_post_id (updateOrCreate), terms/media on
 *    wp_term_id/wp_post_id (firstOrCreate) — re-running the same file mutates
 *    in place instead of duplicating.
 *  - Two-pass id remap: original WP ids (_thumbnail_id, category_parent) are
 *    resolved after all rows exist.
 *  - CDATA is handled transparently by SimpleXML; body HTML is NOT entity-decoded.
 *  - Media (featured + inline) is rehosted to the public disk only when
 *    $downloadMedia is set (run BEFORE DNS cutover while the old site is up).
 */
class WxrImporter
{
    /** @var array<string, string> namespace prefix => uri */
    private array $ns = [];

    /** @var array<int, int> wp_term_id => BlogCategory id */
    private array $categoryMap = [];

    /** @var array<int, int> wp attachment id => BlogMedia id */
    private array $mediaMap = [];

    /** @var array<string, int> */
    private array $summary = [
        'categories' => 0,
        'tags' => 0,
        'posts_created' => 0,
        'posts_updated' => 0,
        'media_downloaded' => 0,
        'media_skipped' => 0,
        'items_skipped' => 0,
        'links_relinked' => 0,
    ];

    /** @var list<string> */
    private array $slugCollisions = [];

    /** @var list<int> ids of posts created/updated this run (for the relink pass) */
    private array $importedPostIds = [];

    public function __construct(
        private bool $downloadMedia = false,
        private bool $force = false,
        private bool $dryRun = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("WXR file not found: {$filePath}");
        }

        $xml = @simplexml_load_file($filePath, SimpleXMLElement::class, LIBXML_NOCDATA);

        if ($xml === false || ! isset($xml->channel)) {
            throw new RuntimeException('Invalid WXR file: not a WordPress export (missing <channel>).');
        }

        $this->ns = $xml->getNamespaces(true);
        $channel = $xml->channel;

        $this->importCategories($channel);
        $this->importTags($channel);
        $this->importAttachments($channel);
        $this->importPosts($channel);
        $this->relinkInternalLinks();

        return $this->result();
    }

    // -----------------------------------------------------------------
    // Terms
    // -----------------------------------------------------------------

    private function importCategories(SimpleXMLElement $channel): void
    {
        $wpChannel = $channel->children($this->ns['wp'] ?? '');

        // slug => [wp_term_id, name, parent_slug, description]. Prefer channel-level
        // <wp:category> blocks (carry term_id + hierarchy); fall back to per-item
        // <category domain="category"> tags for exports that omit channel term defs.
        $rows = [];
        foreach ($wpChannel->category ?? [] as $cat) {
            $c = $cat->children($this->ns['wp'] ?? '');
            $slug = (string) $c->category_nicename;
            if ($slug === '') {
                continue;
            }
            $rows[$slug] = [
                'wp_term_id' => (int) $c->term_id,
                'name' => (string) $c->cat_name,
                'parent_slug' => (string) $c->category_parent,
                'description' => (string) $c->category_description,
            ];
        }

        foreach ($channel->item as $item) {
            foreach ($item->category as $c) {
                if ((string) $c['domain'] !== 'category') {
                    continue;
                }
                $slug = (string) $c['nicename'];
                if ($slug === '' || isset($rows[$slug])) {
                    continue;
                }
                $rows[$slug] = ['wp_term_id' => 0, 'name' => trim((string) $c) ?: $slug, 'parent_slug' => '', 'description' => ''];
            }
        }

        foreach ($rows as $slug => $row) {
            $this->summary['categories']++;

            if ($this->dryRun) {
                continue;
            }

            $category = BlogCategory::firstOrNew(['slug' => $slug]);
            $category->name = $row['name'] ?: Str::title(str_replace('-', ' ', $slug));
            $category->description = $row['description'] ?: $category->description;
            $category->wp_term_id = $row['wp_term_id'] ?: $category->wp_term_id;
            $category->save();

            if ($row['wp_term_id']) {
                $this->categoryMap[$row['wp_term_id']] = $category->id;
            }
        }

        // Second pass: resolve parents by slug (channel-defined hierarchy only).
        if (! $this->dryRun) {
            foreach ($rows as $slug => $row) {
                if ($row['parent_slug'] === '') {
                    continue;
                }
                $child = BlogCategory::where('slug', $slug)->first();
                $parent = BlogCategory::where('slug', $row['parent_slug'])->first();
                if ($child && $parent && $child->id !== $parent->id) {
                    $child->parent_id = $parent->id;
                    $child->save();
                }
            }
        }
    }

    private function importTags(SimpleXMLElement $channel): void
    {
        $wpChannel = $channel->children($this->ns['wp'] ?? '');

        // slug => [name, wp_term_id]; union of channel <wp:tag> and per-item
        // <category domain="post_tag"> tags.
        $rows = [];
        foreach ($wpChannel->tag ?? [] as $tag) {
            $t = $tag->children($this->ns['wp'] ?? '');
            $slug = (string) $t->tag_slug;
            if ($slug === '') {
                continue;
            }
            $rows[$slug] = ['name' => (string) $t->tag_name, 'wp_term_id' => (int) $t->term_id ?: null];
        }

        foreach ($channel->item as $item) {
            foreach ($item->category as $c) {
                if ((string) $c['domain'] !== 'post_tag') {
                    continue;
                }
                $slug = (string) $c['nicename'];
                if ($slug === '' || isset($rows[$slug])) {
                    continue;
                }
                $rows[$slug] = ['name' => trim((string) $c) ?: $slug, 'wp_term_id' => null];
            }
        }

        foreach ($rows as $slug => $row) {
            $this->summary['tags']++;

            if ($this->dryRun) {
                continue;
            }

            BlogTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $row['name'] ?: Str::title(str_replace('-', ' ', $slug)), 'wp_term_id' => $row['wp_term_id']],
            );
        }
    }

    // -----------------------------------------------------------------
    // Attachments (media)
    // -----------------------------------------------------------------

    private function importAttachments(SimpleXMLElement $channel): void
    {
        foreach ($channel->item as $item) {
            $wp = $item->children($this->ns['wp'] ?? '');
            if ((string) $wp->post_type !== 'attachment') {
                continue;
            }

            $attachmentId = (int) $wp->post_id;
            $url = (string) $wp->attachment_url ?: (string) $item->guid;
            if ($url === '') {
                continue;
            }

            $meta = $this->collectPostMeta($wp);
            $relPath = $meta['_wp_attached_file'] ?? $this->uploadsRelativePath($url);

            if ($this->dryRun) {
                $this->summary[$this->downloadMedia ? 'media_downloaded' : 'media_skipped']++;

                continue;
            }

            $media = BlogMedia::firstOrNew(['wp_post_id' => $attachmentId]);
            $media->original_url = $url;
            $media->original_path = $relPath;
            $media->disk_path = 'blog/uploads/'.ltrim($relPath, '/');

            if ($this->downloadMedia && ($this->force || ! $media->exists || ! Storage::disk('public')->exists($media->disk_path))) {
                if ($this->fetchToDisk($url, $media->disk_path)) {
                    $this->summary['media_downloaded']++;
                } else {
                    $this->summary['media_skipped']++;
                }
            } else {
                $this->summary['media_skipped']++;
            }

            $media->mime_type = $media->mime_type ?: $this->guessMime($relPath);
            $media->save();

            $this->mediaMap[$attachmentId] = $media->id;
        }
    }

    // -----------------------------------------------------------------
    // Posts
    // -----------------------------------------------------------------

    private function importPosts(SimpleXMLElement $channel): void
    {
        foreach ($channel->item as $item) {
            $wp = $item->children($this->ns['wp'] ?? '');
            $postType = (string) $wp->post_type;
            $status = (string) $wp->status;

            if ($postType !== 'post' || $status === 'auto-draft') {
                if ($postType !== 'attachment') {
                    $this->summary['items_skipped']++;
                }

                continue;
            }

            $wpPostId = (int) $wp->post_id;
            $slug = $this->resolveSlug($item, $wp, $wpPostId);
            $content = (string) $item->children($this->ns['content'] ?? '')->encoded;
            $excerpt = (string) $item->children($this->ns['excerpt'] ?? '')->encoded;
            $meta = $this->collectPostMeta($wp);

            $publishedAt = $this->parseDate((string) $wp->post_date_gmt)
                ?? $this->parseDate((string) $wp->post_date);
            $createdAt = $this->parseDate((string) $wp->post_date) ?? $publishedAt ?? now();
            $modifiedAt = $this->parseDate((string) $wp->post_modified_gmt)
                ?? $this->parseDate((string) $wp->post_modified) ?? $createdAt;

            [$mappedStatus, $trashed] = $this->mapStatus($status, $publishedAt);

            $exists = Post::withTrashed()->where('wp_post_id', $wpPostId)->exists();

            if ($this->dryRun) {
                $this->summary[$exists ? 'posts_updated' : 'posts_created']++;

                continue;
            }

            $sanitized = HtmlSanitizer::clean($this->rewriteInlineMedia($content));

            $post = Post::withTrashed()->firstOrNew(['wp_post_id' => $wpPostId]);
            $wasExisting = $post->exists;

            $post->fill([
                'title' => (string) $item->title,
                'slug' => $slug,
                'excerpt' => $excerpt ?: null,
                'content' => $sanitized,
                'status' => $mappedStatus,
                'published_at' => $publishedAt,
                'reading_minutes' => Post::estimateReadingMinutes($content),
                'wp_author_login' => (string) $item->children($this->ns['dc'] ?? '')->creator ?: null,
                'wp_guid' => (string) $item->guid ?: null,
                'canonical_url' => $meta['_yoast_wpseo_canonical'] ?? null,
                'legacy_url' => (string) $item->link ?: null,
                'meta_seo' => $this->buildMetaSeo($meta),
            ]);
            $post->created_at = $createdAt;
            $post->updated_at = $modifiedAt;
            $post->save();

            if ($trashed && ! $post->trashed()) {
                $post->delete();
            } elseif (! $trashed && $post->trashed()) {
                $post->restore();
            }

            $this->attachTaxonomy($post, $item);
            $this->applyFeaturedImage($post, $meta);
            $this->applyPrimaryCategory($post, $meta);
            $post->save();

            $this->importedPostIds[] = $post->id;
            $this->summary[$wasExisting ? 'posts_updated' : 'posts_created']++;
        }
    }

    /**
     * Final pass: rewrite old masfirmanpratama.com cross-post links in the
     * content of every post touched this run to the new /blog/{slug} route.
     * Runs after all posts exist so every target slug is resolvable.
     */
    private function relinkInternalLinks(): void
    {
        if ($this->dryRun || $this->importedPostIds === []) {
            return;
        }

        $rewriter = new InternalLinkRewriter;

        Post::withTrashed()->whereIn('id', $this->importedPostIds)
            ->get(['id', 'content'])
            ->each(function (Post $post) use ($rewriter): void {
                $content = $rewriter->rewrite((string) $post->content);
                if ($content !== $post->content) {
                    $post->content = $content;
                    $post->timestamps = false;
                    $post->saveQuietly();
                }
            });

        $this->summary['links_relinked'] = $rewriter->rewrittenCount();
    }

    private function attachTaxonomy(Post $post, SimpleXMLElement $item): void
    {
        $categoryIds = [];
        $tagIds = [];

        foreach ($item->category as $c) {
            $domain = (string) $c['domain'];
            $nicename = (string) $c['nicename'];
            $name = trim((string) $c);
            if ($nicename === '') {
                continue;
            }

            if ($domain === 'category') {
                $cat = BlogCategory::firstOrCreate(['slug' => $nicename], ['name' => $name ?: $nicename]);
                $categoryIds[] = $cat->id;
            } elseif ($domain === 'post_tag') {
                $tag = BlogTag::firstOrCreate(['slug' => $nicename], ['name' => $name ?: $nicename]);
                $tagIds[] = $tag->id;
            }
        }

        $post->categories()->sync(array_unique($categoryIds));
        $post->tags()->sync(array_unique($tagIds));
    }

    private function applyFeaturedImage(Post $post, array $meta): void
    {
        $thumbId = isset($meta['_thumbnail_id']) ? (int) $meta['_thumbnail_id'] : 0;
        if ($thumbId === 0 || ! isset($this->mediaMap[$thumbId])) {
            return;
        }

        $media = BlogMedia::find($this->mediaMap[$thumbId]);
        if ($media) {
            // When rehosted, point at the local /storage copy; otherwise keep the
            // old absolute URL so the image still loads until DNS cutover.
            $post->image_path = $this->downloadMedia ? $media->assetPath() : $media->original_url;
        }
    }

    private function applyPrimaryCategory(Post $post, array $meta): void
    {
        $wpTermId = isset($meta['_yoast_wpseo_primary_category']) ? (int) $meta['_yoast_wpseo_primary_category'] : 0;
        if ($wpTermId && isset($this->categoryMap[$wpTermId])) {
            $post->primary_category_id = $this->categoryMap[$wpTermId];
        } else {
            $post->primary_category_id = $post->categories()->value('blog_categories.id');
        }
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private function collectPostMeta(SimpleXMLElement $wp): array
    {
        $out = [];
        foreach ($wp->postmeta ?? [] as $pm) {
            $m = $pm->children($this->ns['wp'] ?? '');
            $key = (string) $m->meta_key;
            if ($key !== '') {
                $out[$key] = (string) $m->meta_value;
            }
        }

        return $out;
    }

    private function resolveSlug(SimpleXMLElement $item, SimpleXMLElement $wp, int $wpPostId): string
    {
        $slug = (string) $wp->post_name;
        $slug = $slug !== '' ? rawurldecode($slug) : Str::slug((string) $item->title);
        $slug = Str::slug($slug) ?: 'post-'.$wpPostId;

        // Keep the slug stable for this wp_post_id; only de-dupe against a
        // DIFFERENT post that already owns it.
        $clash = Post::withTrashed()
            ->where('slug', $slug)
            ->where('wp_post_id', '!=', $wpPostId)
            ->exists();

        if ($clash) {
            $slug .= '-wp'.$wpPostId;
            $this->slugCollisions[] = $slug;
        }

        return $slug;
    }

    /**
     * @return array{0: string, 1: bool} [status, isTrashed]
     */
    private function mapStatus(string $wpStatus, ?Carbon $publishedAt): array
    {
        return match ($wpStatus) {
            'publish' => ['published', false],
            'future' => [$publishedAt && $publishedAt->isFuture() ? 'scheduled' : 'published', false],
            'trash' => ['draft', true],
            default => ['draft', false], // draft, pending, private, inherit, ...
        };
    }

    private function parseDate(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string>  $meta
     * @return array<string, string>|null
     */
    private function buildMetaSeo(array $meta): ?array
    {
        $seo = array_filter([
            'title' => $meta['_yoast_wpseo_title'] ?? null,
            'description' => $meta['_yoast_wpseo_metadesc'] ?? null,
            'canonical' => $meta['_yoast_wpseo_canonical'] ?? null,
            'og_title' => $meta['_yoast_wpseo_opengraph-title'] ?? null,
            'og_description' => $meta['_yoast_wpseo_opengraph-description'] ?? null,
            'og_image' => $meta['_yoast_wpseo_opengraph-image'] ?? null,
            'focus_keyword' => $meta['_yoast_wpseo_focuskw'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $seo ?: null;
    }

    /**
     * Rewrite inline wp-content/uploads image URLs to the rehosted /storage path.
     * Only meaningful when media was downloaded; strips WP responsive size
     * suffixes (-1024x585) so they resolve to the stored original.
     */
    private function rewriteInlineMedia(string $content): string
    {
        if (! $this->downloadMedia) {
            return $content;
        }

        return preg_replace_callback(
            '#(https?:)?//[^"\'\s)]+?/wp-content/uploads/([^"\'\s)]+)#i',
            function (array $m): string {
                $rel = preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $m[2]);

                return asset('storage/blog/uploads/'.$rel);
            },
            $content,
        ) ?? $content;
    }

    private function uploadsRelativePath(string $url): string
    {
        if (preg_match('#/wp-content/uploads/(.+)$#i', $url, $m)) {
            return $m[1];
        }

        return ltrim(parse_url($url, PHP_URL_PATH) ?: basename($url), '/');
    }

    private function fetchToDisk(string $url, string $diskPath): bool
    {
        try {
            // Fail fast on dead/slow hosts: a bounded per-file budget stops one
            // unreachable image URL from eating the whole request's time.
            $response = Http::connectTimeout(8)->timeout(15)->get($url);
            if (! $response->successful()) {
                return false;
            }
            Storage::disk('public')->put($diskPath, $response->body());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function guessMime(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function result(): array
    {
        return [
            'summary' => $this->summary,
            'slug_collisions' => $this->slugCollisions,
            'dry_run' => $this->dryRun,
            'download_media' => $this->downloadMedia,
        ];
    }
}
