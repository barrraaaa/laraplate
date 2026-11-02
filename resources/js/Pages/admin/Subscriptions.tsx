import AdminLayout from '@/components/layouts/admin-layout';
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

type SubscriptionsProps = {
    subscriptions: {
        data: {
            id: number;
            status: string;
            price: string | null;
            owner: string;
            endsAt: string | null;
            createdAt: string;
        }[];
    };
};

export default function Subscriptions() {
    const { subscriptions } = usePage<PageProps<SubscriptionsProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Subscriptions" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Subscriptions
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Every Stripe subscription known to the platform.
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Owner
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead className="text-right">
                                        Ends
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subscriptions.data.map((subscription) => (
                                    <TableRow key={subscription.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {subscription.owner}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    subscription.status ===
                                                        'active' ||
                                                    subscription.status ===
                                                        'trialing'
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                            >
                                                {subscription.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs">
                                            {subscription.price ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {subscription.endsAt
                                                ? new Date(
                                                      subscription.endsAt,
                                                  ).toLocaleDateString()
                                                : '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {subscriptions.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No subscriptions yet — they appear
                                            once Stripe webhooks run.
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
