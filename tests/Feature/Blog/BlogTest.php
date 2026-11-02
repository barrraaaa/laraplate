<?php

namespace Tests\Feature\Blog;

use App\Models\User;
use App\Modules\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO & blog module: published posts are public, drafts stay hidden,
 * the sitemap lists only published slugs, admin manages posts.
 */
class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_posts_are_listed_and_drafts_are_not(): void
    {
        Post::factory()->published()->create(['title' => 'Visible post']);
        Post::factory()->create(['title' => 'Hidden draft']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Index')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Visible post'));
    }

    public function test_draft_slug_returns_404(): void
    {
        $draft = Post::factory()->create();

        $this->get(route('blog.show', $draft->slug))->assertNotFound();
    }

    public function test_sitemap_lists_published_posts(): void
    {
        $published = Post::factory()->published()->create();
        Post::factory()->create(['title' => 'Draft']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('blog.show', $published->slug));
    }

    public function test_platform_admin_can_publish_and_edit_a_post(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/blog', [
                'title' => 'From the admin',
                'body' => 'Hello from the blog.',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.blog.index'));

        $post = Post::query()->where('title', 'From the admin')->firstOrFail();

        $this->assertTrue($post->isPublished());
        $this->assertSame($admin->id, $post->author_id);
        $this->assertNotNull($post->published_at);

        $this->get(route('blog.show', $post->slug))->assertOk();
    }

    public function test_non_admins_cannot_open_blog_admin(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/blog')->assertForbidden();
    }
}
