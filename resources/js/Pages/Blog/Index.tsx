import ApplicationLogo from '@/components/shared/application-logo';
import Seo from '@/components/shared/seo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Button } from '@/components/ui/button';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

type PostItem = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    author: string | null;
    publishedAt: string | null;
};

type BlogIndexProps = {
    posts: {
        data: PostItem[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

/**
 * Public blog index (SEO & blog module).
 */
export default function Index() {
    const { posts } = usePage<PageProps<BlogIndexProps>>().props;

    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Seo
                title="Blog"
                description="Guides and updates for building products with Laraplate."
                canonical={`${window.location.origin}/blog`}
            />

            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <Link href="/" className="flex items-center gap-2.5">
                        <ApplicationLogo className="size-6" />
                        <span className="font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                        </span>
                    </Link>
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/">Home</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-12">
                <h1 className="text-3xl font-semibold tracking-tight">Blog</h1>
                <p className="text-muted-foreground mt-2">
                    Guides, changelog notes and launch stories.
                </p>

                <div className="mt-10 space-y-4">
                    {posts.data.map((post) => (
                        <Link
                            key={post.id}
                            href={`/blog/${post.slug}`}
                            className="group hover:bg-muted/40 block rounded-xl border p-5 transition-colors"
                        >
                            <h2 className="text-lg font-semibold group-hover:underline">
                                {post.title}
                            </h2>
                            {post.excerpt && (
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {post.excerpt}
                                </p>
                            )}
                            <div className="text-muted-foreground mt-3 flex items-center justify-between text-xs">
                                <span>
                                    {post.author} ·{' '}
                                    {post.publishedAt
                                        ? new Date(
                                              post.publishedAt,
                                          ).toLocaleDateString()
                                        : ''}
                                </span>
                                <span className="flex items-center gap-1">
                                    Read <ArrowRight className="size-3" />
                                </span>
                            </div>
                        </Link>
                    ))}

                    {posts.data.length === 0 && (
                        <p className="text-muted-foreground rounded-xl border border-dashed px-4 py-16 text-center text-sm">
                            No posts published yet — write one from the admin
                            panel.
                        </p>
                    )}
                </div>

                {posts.last_page > 1 && (
                    <div className="mt-8 flex items-center justify-between">
                        {posts.links
                            .filter((link) => link.url)
                            .map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.url as string}
                                    className="text-sm font-medium underline-offset-4 hover:underline"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                    </div>
                )}
            </main>
        </div>
    );
}
