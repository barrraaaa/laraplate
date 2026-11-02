import AdminLayout from '@/components/layouts/admin-layout';
import StatusBadge from '@/components/shared/status-badge';
import { Badge } from '@/components/ui/badge';
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

type EndpointItem = {
    id: number;
    organization: string;
    url: string;
    events: string[];
    active: boolean;
    deliveriesCount: number;
    createdAt: string;
};

type DeliveryItem = {
    id: number;
    organization: string;
    event: string;
    attemptCount: number;
    responseCode: number | null;
    delivered: boolean;
    createdAt: string;
};

type WebhooksProps = {
    endpoints: { data: EndpointItem[] };
    recentDeliveries: DeliveryItem[];
};

/**
 * Admin webhook overview: every registered endpoint and the latest
 * deliveries across organizations.
 */
export default function Webhooks() {
    const { endpoints, recentDeliveries } =
        usePage<PageProps<WebhooksProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Webhooks" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Webhooks
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Outbound endpoints across all organizations.
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Organization
                                    </TableHead>
                                    <TableHead>URL</TableHead>
                                    <TableHead>Events</TableHead>
                                    <TableHead className="text-right">
                                        Deliveries
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Status
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Created
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {endpoints.data.map((endpoint) => (
                                    <TableRow key={endpoint.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {endpoint.organization}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground max-w-52 truncate text-xs">
                                            {endpoint.url}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {endpoint.events
                                                    .slice(0, 3)
                                                    .map((event) => (
                                                        <Badge
                                                            key={event}
                                                            variant="outline"
                                                            className="font-mono text-[10px]"
                                                        >
                                                            {event}
                                                        </Badge>
                                                    ))}
                                                {endpoint.events.length > 3 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        +
                                                        {endpoint.events
                                                            .length - 3}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {endpoint.deliveriesCount}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <StatusBadge
                                                tone={
                                                    endpoint.active
                                                        ? 'success'
                                                        : 'neutral'
                                                }
                                            >
                                                {endpoint.active
                                                    ? 'active'
                                                    : 'paused'}
                                            </StatusBadge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                endpoint.createdAt,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {endpoints.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No endpoints yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Organization
                                    </TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Attempts
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        When
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentDeliveries.map((delivery) => (
                                    <TableRow key={delivery.id}>
                                        <TableCell className="pl-6 text-sm">
                                            {delivery.organization}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">
                                            {delivery.event}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                tone={
                                                    delivery.delivered
                                                        ? 'success'
                                                        : delivery.responseCode
                                                          ? 'destructive'
                                                          : 'warning'
                                                }
                                            >
                                                {delivery.delivered
                                                    ? 'delivered'
                                                    : (delivery.responseCode ??
                                                      'pending')}
                                            </StatusBadge>
                                        </TableCell>
                                        <TableCell className="text-right text-xs">
                                            {delivery.attemptCount}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                delivery.createdAt,
                                            ).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {recentDeliveries.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No deliveries yet.
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
