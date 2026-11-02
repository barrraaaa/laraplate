import AdminLayout from '@/components/layouts/admin-layout';
import StatusBadge, {
    transactionStatusTone,
} from '@/components/shared/status-badge';
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

type TransactionsProps = {
    transactions: {
        data: {
            id: number;
            organization: string | null;
            eventType: string;
            amount: string;
            currency: string;
            status: string;
            createdAt: string;
        }[];
    };
};

export default function Transactions() {
    const { transactions } = usePage<PageProps<TransactionsProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Transactions" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Transactions
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Payment events recorded from Stripe webhooks.
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
                                    <TableHead>Event</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Date
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transactions.data.map((transaction) => (
                                    <TableRow key={transaction.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {transaction.organization ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs">
                                            {transaction.eventType}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                tone={transactionStatusTone(
                                                    transaction.status,
                                                )}
                                            >
                                                {transaction.status}
                                            </StatusBadge>
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {transaction.amount}{' '}
                                            {transaction.currency}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                transaction.createdAt,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {transactions.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No transactions yet.
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
