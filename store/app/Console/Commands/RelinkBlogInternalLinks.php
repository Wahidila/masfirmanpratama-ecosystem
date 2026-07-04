<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Blog\InternalLinkRewriter;
use Illuminate\Console\Command;

/**
 * Reformat legacy masfirmanpratama.com article links inside existing post
 * content to the new /blog/{slug} route. Idempotent — once a link is /blog/...
 * it no longer matches, so re-running is safe.
 */
class RelinkBlogInternalLinks extends Command
{
    protected $signature = 'blog:relink
        {--dry-run : Report what would change without writing to the database}';

    protected $description = 'Rewrite old masfirmanpratama.com article links in post content to /blog/{slug}';

    public function handle(InternalLinkRewriter $rewriter): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $postsChanged = 0;
        $totalLinks = 0;

        Post::withTrashed()->chunkById(100, function ($posts) use ($rewriter, $dryRun, &$postsChanged, &$totalLinks) {
            foreach ($posts as $post) {
                $before = $rewriter->rewrittenCount();
                $content = $rewriter->rewrite((string) $post->content);
                $delta = $rewriter->rewrittenCount() - $before;

                if ($delta === 0 || $content === $post->content) {
                    continue;
                }

                $postsChanged++;
                $totalLinks += $delta;

                if (! $dryRun) {
                    // Mechanical migration, not an editorial edit: keep updated_at
                    // and don't fire model events.
                    $post->content = $content;
                    $post->timestamps = false;
                    $post->saveQuietly();
                }
            }
        });

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Artikel diperbarui: {$postsChanged} — link internal dirapikan: {$totalLinks}.");

        $unmatched = $rewriter->unmatchedTargets();
        if ($unmatched !== []) {
            $this->warn(
                'Diarahkan ke /blog/{slug} tapi post-nya belum ada (import artikel ini atau cek manual): '
                .implode(', ', $unmatched)
            );
        }

        return self::SUCCESS;
    }
}
