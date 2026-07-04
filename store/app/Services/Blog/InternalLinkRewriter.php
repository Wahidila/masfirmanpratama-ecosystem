<?php

namespace App\Services\Blog;

use App\Models\Post;

/**
 * Rewrites legacy masfirmanpratama.com article links found inside post content
 * to the new on-site blog route (/blog/{slug}).
 *
 * The old WordPress site linked cross-posts two ways:
 *  - date permalinks:  https://masfirmanpratama.com/2025/03/01/some-slug/
 *  - postname permalinks: https://masfirmanpratama.com/some-slug/
 * The new site keeps the SAME domain but serves posts under /blog/{slug}, so a
 * bare "/some-slug" or "/2025/03/01/some-slug" would 404. This rewriter maps the
 * URL back to a known post and emits a relative "/blog/{slug}" link.
 *
 * Safety:
 *  - Only rewrites <a href> values whose host is masfirmanpratama.com.
 *  - Date-permalink URLs are unambiguously blog articles → always rewritten to
 *    /blog/{last-segment} (resolved to the real slug when the post exists).
 *  - Bare "/slug" URLs are rewritten ONLY when a post with that slug exists, so
 *    pages/products/homepage on the old domain are never touched.
 *  - wp-content/uploads (images), /category, /tag, /kelas, query-only and
 *    external links are left untouched.
 */
class InternalLinkRewriter
{
    /** @var array<string, string> lowercased key (slug or legacy last-segment) => canonical post slug */
    private array $slugByKey = [];

    private int $rewritten = 0;

    /** @var list<string> target slugs rewritten by date-pattern with no matching post */
    private array $unmatched = [];

    public function __construct()
    {
        $this->loadMap();
    }

    private function loadMap(): void
    {
        $posts = Post::withTrashed()->get(['slug', 'legacy_url']);

        // First pass: legacy permalink last-segment (original WP post_name), so
        // date-based inline links written before a slug collision still resolve.
        foreach ($posts as $post) {
            $seg = $this->lastSegment((string) $post->legacy_url);
            if ($seg !== null) {
                $this->slugByKey[$seg] = $post->slug;
            }
        }

        // Second pass: the canonical stored slug always wins on conflict.
        foreach ($posts as $post) {
            $this->slugByKey[mb_strtolower($post->slug)] = $post->slug;
        }
    }

    /**
     * Rewrite every qualifying href in the given HTML. Counts accumulate across
     * calls so a single instance can walk many posts.
     */
    public function rewrite(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'masfirmanpratama.com')) {
            return $html;
        }

        return preg_replace_callback(
            '#\bhref\s*=\s*(["\'])(.*?)\1#is',
            function (array $m): string {
                $new = $this->rewriteUrl(trim($m[2]));
                if ($new === null) {
                    return $m[0];
                }
                $this->rewritten++;

                return 'href='.$m[1].$new.$m[1];
            },
            $html,
        ) ?? $html;
    }

    private function rewriteUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null; // relative or malformed — leave alone
        }

        $host = strtolower($parts['host']);
        if ($host !== 'masfirmanpratama.com' && $host !== 'www.masfirmanpratama.com') {
            return null;
        }

        $slug = $this->resolveSlug($parts['path'] ?? '/');
        if ($slug === null) {
            return null;
        }

        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#'.$parts['fragment'] : '';

        return '/blog/'.$slug.$fragment;
    }

    private function resolveSlug(string $path): ?string
    {
        $path = '/'.trim(rawurldecode($path), '/');

        // Date permalink: /YYYY/MM[/DD]/slug — the last segment is the post.
        if (preg_match('#^/\d{4}/\d{2}(?:/\d{2})?/([a-z0-9\-]+)$#i', $path, $m)) {
            $key = mb_strtolower($m[1]);
            if (isset($this->slugByKey[$key])) {
                return $this->slugByKey[$key];
            }

            // Unambiguously an old blog article on the same domain — normalise to
            // /blog/{slug} even if the post isn't imported yet, and flag it.
            $this->unmatched[] = $m[1];

            return $m[1];
        }

        // Bare postname permalink: /slug — rewrite ONLY when it maps to a real
        // post (never touch old pages/products that share this shape).
        if (preg_match('#^/([a-z0-9\-]+)$#i', $path, $m)) {
            return $this->slugByKey[mb_strtolower($m[1])] ?? null;
        }

        return null;
    }

    private function lastSegment(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $segs = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));
        if ($segs === []) {
            return null;
        }

        return mb_strtolower((string) end($segs));
    }

    public function rewrittenCount(): int
    {
        return $this->rewritten;
    }

    /** @return list<string> */
    public function unmatchedTargets(): array
    {
        return array_values(array_unique($this->unmatched));
    }
}
