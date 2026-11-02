import ApplicationLogo from '@/components/shared/application-logo';
import Seo from '@/components/shared/seo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Button } from '@/components/ui/button';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

type PostItem = {
    id: number;
    title: string;
    slug: string;
    body: string;
    author: string | null;
    publishedAt: string | null;
    metaTitle: string | null;
    metaDescription: string | null;
};

/**
 * Public blog post (SEO meta from the post itself).
 */
export default function Show() {
    const { post } = usePage<PageProps<{ post: PostItem }>>().props;

    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Seo
                title={post.metaTitle || post.title}
                description={post.metaDescription || undefined}
                canonical={`${window.location.origin}/blog/${post.slug}`}
            />

            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-3xl items-center justify-between px-4">
                    <Link href="/" className="flex items-center gap-2.5">
                        <ApplicationLogo className="size-6" />
                        <span className="font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                        </span>
                    </Link>
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={route('blog.index')}>Blog</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-12">
                <h1 className="text-3xl font-semibold tracking-tight">
                    {post.title}
                </h1>
                <p className="text-muted-foreground mt-3 text-sm">
                    {post.author} ·{' '}
                    {post.publishedAt
                        ? new Date(post.publishedAt).toLocaleDateString()
                        : ''}
                </p>

                <article className="mt-8 space-y-5 text-[15px] leading-relaxed whitespace-pre-wrap">
                    {post.body}
                </article>
            </main>
        </div>
    );
}
