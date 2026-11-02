<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public blog (SEO & blog module): published posts with pagination and
 * per-post SEO metadata.
 */
class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = Post::query()
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->paginate(10)
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'author' => $post->author?->name,
                'publishedAt' => $post->published_at?->toIso8601String(),
            ]);

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(Post $post): Response
    {
        abort_unless($post->isPublished(), 404);

        return Inertia::render('Blog/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'body' => $post->body,
                'author' => $post->author?->name,
                'publishedAt' => $post->published_at?->toIso8601String(),
                'metaTitle' => $post->meta_title,
                'metaDescription' => $post->meta_description,
            ],
        ]);
    }
}
