import AdminLayout from '@/components/layouts/admin-layout';
import { Button } from '@/components/ui/button';
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
import { Download } from 'lucide-react';

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
    filters: { action?: string; actor?: string; from?: string; to?: string };
};

/**
 * Admin activity log (PRD 5.9): security and lifecycle events stream,
 * filterable by action, actor and date range, with CSV export.
 */
export default function ActivityLogs() {
    const { events, actions, filters } =
        usePage<PageProps<ActivityProps>>().props;

    const setFilter = (key: string, value: string) => {
        router.get(
            '/admin/activity',
            { ...filters, [key]: value || undefined },
            { preserveState: true, replace: true },
        );
    };

    const exportUrl = () => {
        const params = new URLSearchParams();

        if (filters.action) params.set('action', filters.action);
        if (filters.actor) params.set('actor', filters.actor);
        if (filters.from) params.set('from', filters.from);
        if (filters.to) params.set('to', filters.to);

        return `/admin/activity/export?${params.toString()}`;
    };

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

                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            value={filters.actor ?? ''}
                            placeholder="Filter by actor…"
                            className="h-9 w-52"
                            onChange={(e) => setFilter('actor', e.target.value)}
                        />
                        <Input
                            type="date"
                            title="From"
                            className="h-9 w-40"
                            value={filters.from ?? ''}
                            onChange={(e) => setFilter('from', e.target.value)}
                        />
                        <Input
                            type="date"
                            title="To"
                            className="h-9 w-40"
                            value={filters.to ?? ''}
                            onChange={(e) => setFilter('to', e.target.value)}
                        />
                        <select
                            value={filters.action ?? ''}
                            onChange={(e) =>
                                setFilter('action', e.target.value)
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
                        <Button size="sm" variant="outline" asChild>
                            <a href={exportUrl()}>
                                <Download className="mr-1.5 size-4" />
                                Export CSV
                            </a>
                        </Button>
                    </div>
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
