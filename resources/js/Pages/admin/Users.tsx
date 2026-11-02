import AdminLayout from '@/components/layouts/admin-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

type UsersProps = {
    users: {
        data: {
            id: number;
            name: string;
            email: string;
            verified: boolean;
            organizationsCount: number;
            createdAt: string;
        }[];
        current_page: number;
        last_page: number;
    };
    filters: { search?: string };
};

/**
 * Admin user management per the blueprint: searchable customer table.
 */
export default function Users() {
    const { users, filters } = usePage<PageProps<UsersProps>>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', search ? { search } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AdminLayout>
            <Head title="Admin — Users" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Users
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Every account on the platform.
                        </p>
                    </div>
                    <form onSubmit={submit} className="relative w-64">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search name or email…"
                            className="pl-8"
                        />
                    </form>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">User</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Organizations
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Joined
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="pl-6">
                                            <div className="text-sm font-medium">
                                                {user.name}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {user.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.verified ? (
                                                <Badge variant="secondary">
                                                    Verified
                                                </Badge>
                                            ) : (
                                                <Badge variant="destructive">
                                                    Unverified
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {user.organizationsCount}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                user.createdAt,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No users found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {users.last_page > 1 && (
                    <Pagination
                        current={users.current_page}
                        last={users.last_page}
                        basePath="/admin/users"
                        query={search ? { search } : {}}
                    />
                )}
            </div>
        </AdminLayout>
    );
}

function Pagination({
    current,
    last,
    basePath,
    query,
}: {
    current: number;
    last: number;
    basePath: string;
    query: Record<string, string>;
}) {
    return (
        <div className="flex items-center justify-end gap-2 text-sm">
            <button
                type="button"
                disabled={current <= 1}
                onClick={() =>
                    router.get(
                        basePath,
                        { ...query, page: current - 1 },
                        { preserveState: true },
                    )
                }
                className="hover:bg-secondary rounded-md border px-3 py-1.5 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Previous
            </button>
            <span className="text-muted-foreground text-xs">
                Page {current} of {last}
            </span>
            <button
                type="button"
                disabled={current >= last}
                onClick={() =>
                    router.get(
                        basePath,
                        { ...query, page: current + 1 },
                        { preserveState: true },
                    )
                }
                className="hover:bg-secondary rounded-md border px-3 py-1.5 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Next
            </button>
        </div>
    );
}
