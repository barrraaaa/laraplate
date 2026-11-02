import AdminLayout from '@/components/layouts/admin-layout';
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
import { Head, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

type NotificationsProps = {
    stream: {
        id: string;
        user: string;
        userEmail: string | null;
        title: string;
        read: boolean;
        createdAt: string;
    }[];
};

/**
 * Admin visibility over the in-app notification stream (blueprint
 * screen) — who got notified, and whether they read it.
 */
export default function Notifications() {
    const { stream: notifications } =
        usePage<PageProps<NotificationsProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Notifications" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Notifications
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Latest in-app notifications across all users.
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Message
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead className="text-right">
                                        State
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        When
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {notifications.map((notification) => (
                                    <TableRow key={notification.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-2 text-sm font-medium">
                                                <Bell className="text-muted-foreground size-4" />
                                                {notification.title}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {notification.user}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {notification.userEmail}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right text-xs">
                                            {notification.read ? (
                                                <span className="text-muted-foreground">
                                                    Read
                                                </span>
                                            ) : (
                                                <span className="text-primary font-medium">
                                                    Unread
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                notification.createdAt,
                                            ).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {notifications.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No notifications yet.
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
