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
import { Head, router, usePage } from '@inertiajs/react';

type ActivityProps = {
    events: {
        data: {
            id: number;
            action: string;
            actor: string;
            organization: string | null;
            ipAddress: string | null;
            createdAt: string;
        }[];
        current_page: number;
        last_page: number;
    };
    actions: string[];
    filters: { action?: string };
};

/**
 * Admin activity log (PRD 5.9): security and lifecycle events stream,
 * filterable by action.
 */
export default function ActivityLogs() {
    const { events, actions, filters } =
        usePage<PageProps<ActivityProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Activity Logs" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Activity logs
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Audited security and lifecycle events across the
                            platform.
                        </p>
                    </div>

                    <select
                        value={filters.action ?? ''}
                        onChange={(e) =>
                            router.get(
                                '/admin/activity',
                                e.target.value
                                    ? { action: e.target.value }
                                    : {},
                                { preserveState: true, replace: true },
                            )
                        }
                        className="bg-background h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="">All actions</option>
                        {actions.map((action) => (
                            <option key={action} value={action}>
                                {action}
                            </option>
                        ))}
                    </select>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Action
                                    </TableHead>
                                    <TableHead>Actor</TableHead>
                                    <TableHead>Organization</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        When
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {events.data.map((event) => (
                                    <TableRow key={event.id}>
                                        <TableCell className="pl-6 font-mono text-xs font-medium">
                                            {event.action}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {event.actor}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {event.organization ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                event.createdAt,
                                            ).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {events.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No events yet.
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
