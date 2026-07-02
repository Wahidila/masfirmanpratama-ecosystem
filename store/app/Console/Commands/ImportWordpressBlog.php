<?php

namespace App\Console\Commands;

use App\Services\Blog\WxrImporter;
use Illuminate\Console\Command;

class ImportWordpressBlog extends Command
{
    protected $signature = 'blog:import-wordpress
        {file : Path to the WordPress WXR export (.xml)}
        {--dry-run : Parse and report without writing to the database or disk}
        {--media : Download and rehost images from wp-content/uploads}
        {--force : Re-download media even if already present}';

    protected $description = 'Import blog posts, categories, tags, and media from a WordPress WXR export';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        $importer = new WxrImporter(
            downloadMedia: (bool) $this->option('media'),
            force: (bool) $this->option('force'),
            dryRun: (bool) $this->option('dry-run'),
        );

        try {
            $result = $importer->import($file);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($result['dry_run']) {
            $this->warn('DRY RUN — no changes were written.');
        }

        $s = $result['summary'];
        $this->table(['Metric', 'Count'], [
            ['Categories', $s['categories']],
            ['Tags', $s['tags']],
            ['Posts created', $s['posts_created']],
            ['Posts updated', $s['posts_updated']],
            ['Media downloaded', $s['media_downloaded']],
            ['Media skipped', $s['media_skipped']],
            ['Items skipped', $s['items_skipped']],
        ]);

        if (! empty($result['slug_collisions'])) {
            $this->warn('Slug collisions resolved (suffixed): '.implode(', ', $result['slug_collisions']));
        }

        $this->info('WordPress import complete.');

        return self::SUCCESS;
    }
}
