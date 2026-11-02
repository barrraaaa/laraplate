import AdminLayout from '@/components/layouts/admin-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type PostForm = {
    id: number | null;
    title: string;
    slug: string | null;
    excerpt: string | null;
    body: string;
    status: string;
    metaTitle: string | null;
    metaDescription: string | null;
};

/**
 * Blog post editor (create/edit) — admin only.
 */
export default function Edit() {
    const { post } = usePage<PageProps<{ post: PostForm | null }>>().props;

    const [title, setTitle] = useState(post?.title ?? '');
    const [slug, setSlug] = useState(post?.slug ?? '');
    const [excerpt, setExcerpt] = useState(post?.excerpt ?? '');
    const [body, setBody] = useState(post?.body ?? '');
    const [status, setStatus] = useState(post?.status ?? 'draft');
    const [metaTitle, setMetaTitle] = useState(post?.metaTitle ?? '');
    const [metaDescription, setMetaDescription] = useState(
        post?.metaDescription ?? '',
    );
    const [errors, setErrors] = useState<Record<string, string>>({});

    const save = (e: React.FormEvent) => {
        e.preventDefault();

        const payload = {
            title,
            slug,
            excerpt,
            body,
            status,
            meta_title: metaTitle,
            meta_description: metaDescription,
        };

        if (post?.id) {
            router.patch(`/admin/blog/${post.id}`, payload, {
                preserveScroll: true,
                onError: (serverErrors) => setErrors(serverErrors),
            });
        } else {
            router.post('/admin/blog', payload, {
                onError: (serverErrors) => setErrors(serverErrors),
            });
        }
    };

    return (
        <AdminLayout>
            <Head title={post ? `Edit — ${post.title}` : 'New post'} />

            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {post ? 'Edit post' : 'New post'}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Posts publish to the public blog and the sitemap.
                    </p>
                </div>

                <form onSubmit={save} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Content</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                />
                                <FormError message={errors.title} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={slug}
                                    placeholder="auto-generated from title"
                                    onChange={(e) => setSlug(e.target.value)}
                                />
                                <FormError message={errors.slug} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="excerpt">Excerpt</Label>
                                <Textarea
                                    id="excerpt"
                                    value={excerpt}
                                    onChange={(e) => setExcerpt(e.target.value)}
                                />
                                <FormError message={errors.excerpt} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="body">Body</Label>
                                <Textarea
                                    id="body"
                                    value={body}
                                    rows={12}
                                    onChange={(e) => setBody(e.target.value)}
                                />
                                <FormError message={errors.body} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Publishing & SEO
                            </CardTitle>
                            <CardDescription>
                                Meta fields feed the blog's per-post SEO
                                component and the sitemap.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1.5">
                                <Label>Status</Label>
                                <Select
                                    value={status}
                                    onValueChange={setStatus}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">
                                            Draft
                                        </SelectItem>
                                        <SelectItem value="published">
                                            Published
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="meta_title">Meta title</Label>
                                <Input
                                    id="meta_title"
                                    value={metaTitle}
                                    onChange={(e) =>
                                        setMetaTitle(e.target.value)
                                    }
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="meta_description">
                                    Meta description
                                </Label>
                                <Textarea
                                    id="meta_description"
                                    value={metaDescription}
                                    onChange={(e) =>
                                        setMetaDescription(e.target.value)
                                    }
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={title.trim() === '' || body.trim() === ''}
                        >
                            {post ? 'Save post' : 'Create post'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/admin/blog">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
