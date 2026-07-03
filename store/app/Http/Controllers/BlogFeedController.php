<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BlogFeedController extends Controller
{
    /**
     * XML sitemap of published blog posts (+ the blog index) for Search Console.
     * lastmod uses updated_at so re-imported/edited posts are re-crawled.
     */
    public function sitemap(): Response
    {
        $posts = Post::published()->latest('published_at')->get(['slug', 'updated_at', 'published_at']);

        $urls = [];
        $urls[] = [
            'loc' => route('blog.index'),
            'lastmod' => optional($posts->max('updated_at'))->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => optional($post->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            if ($url['lastmod']) {
                $xml .= '    <lastmod>'.e($url['lastmod']).'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * RSS 2.0 feed of the latest published posts.
     */
    public function feed(): Response
    {
        $posts = Post::published()->latest('published_at')->take(20)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '  <channel>'."\n";
        $xml .= '    <title>Blog Firman Pratama</title>'."\n";
        $xml .= '    <link>'.e(route('blog.index')).'</link>'."\n";
        $xml .= '    <description>Wawasan Mind Power &amp; Life Mastery</description>'."\n";
        $xml .= '    <language>id-ID</language>'."\n";
        $xml .= '    <atom:link href="'.e(route('blog.feed')).'" rel="self" type="application/rss+xml" />'."\n";

        foreach ($posts as $post) {
            $desc = $post->excerpt ?: Str::limit(trim(strip_tags($post->content)), 200);
            $xml .= '    <item>'."\n";
            $xml .= '      <title>'.e($post->title).'</title>'."\n";
            $xml .= '      <link>'.e(route('blog.show', $post->slug)).'</link>'."\n";
            $xml .= '      <guid isPermaLink="true">'.e(route('blog.show', $post->slug)).'</guid>'."\n";
            if ($post->published_at) {
                $xml .= '      <pubDate>'.$post->published_at->toRssString().'</pubDate>'."\n";
            }
            $xml .= '      <description>'.e($desc).'</description>'."\n";
            $xml .= '    </item>'."\n";
        }

        $xml .= '  </channel>'."\n";
        $xml .= '</rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
