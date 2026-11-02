import AdminLayout from '@/components/layouts/admin-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PencilLine, Plus, Trash2 } from 'lucide-react';

type BlogAdminProps = {
    posts: {
        data: {
            id: number;
            title: string;
            slug: string;
            status: string;
            author: string | null;
            publishedAt: string | null;
        }[];
        current_page: number;
        last_page: number;
    };
};

/**
 * Admin blog management — posting, unpublishing and deleting posts.
 */
export default function Index() {
    const { posts } = usePage<PageProps<BlogAdminProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Blog" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Blog
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Write and publish posts for the public blog.
                        </p>
                    </div>
                    <Button size="sm" asChild>
                        <Link href="/admin/blog/create">
                            <Plus className="mr-1.5 size-4" />
                            New post
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Title
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Author</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {posts.data.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell className="pl-6">
                                            <div className="text-sm font-medium">
                                                {post.title}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {post.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    post.status === 'published'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {post.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {post.author ?? '—'}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/admin/blog/${post.id}/edit`}
                                                    >
                                                        <PencilLine className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/admin/blog/${post.id}`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="text-destructive size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {posts.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No posts yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
