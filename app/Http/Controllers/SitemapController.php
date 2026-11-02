<?php

namespace App\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Hand-rolled sitemap (deliberately dependency-free): static pages plus
 * published blog posts with their lastmod.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            url('/'),
            route('blog.index'),
            url('/#pricing'),
            route('deals'),
            url('/terms'),
            url('/privacy'),
        ];

        $posts = Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(500)
            ->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url).'</loc></url>';
        }

        foreach ($posts as $post) {
            $xml .= '<url>'
                .'<loc>'.e(route('blog.show', $post->slug)).'</loc>'
                .'<lastmod>'.$post->updated_at->toDateString().'</lastmod>'
                .'</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
