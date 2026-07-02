<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Flip scheduled blog posts to published once their published_at time has arrived';

    public function handle(): int
    {
        $due = Post::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        foreach ($due as $post) {
            $post->status = 'published';
            $post->save();
        }

        $count = $due->count();
        $this->info("Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}
