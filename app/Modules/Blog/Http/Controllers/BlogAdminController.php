<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin blog management (platform-admin gated route group).
 */
class BlogAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        return Inertia::render('admin/Blog/Index', [
            'posts' => Post::query()
                ->with('author:id,name')
                ->orderByDesc('updated_at')
                ->paginate(20)
                ->through(fn (Post $post) => $this->serialize($post)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Blog/Edit', [
            'post' => null,
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('admin/Blog/Edit', [
            'post' => $this->serialize($post),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $post = new Post;
        $post->fillFromAdmin($data, $request->user()->id);
        $post->save();

        return redirect()->route('admin.blog.index')->with('status', 'post-created');
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validated($request, $post);

        $post->fillFromAdmin($data, $request->user()->id);
        $post->save();

        return back()->with('status', 'post-updated');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('status', 'post-deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:200', 'unique:posts,slug'.($post ? ','.$post->id : '')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'status' => $post->status,
            'author' => $post->author?->name,
            'publishedAt' => $post->published_at?->toIso8601String(),
            'metaTitle' => $post->meta_title,
            'metaDescription' => $post->meta_description,
        ];
    }
}
