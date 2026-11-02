import AdminLayout from '@/components/layouts/admin-layout';
import StatusBadge, {
    invoiceStatusTone,
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

type InvoicesProps = {
    invoices: {
        data: {
            id: number;
            organization: string | null;
            number: string;
            total: string;
            status: string;
            createdAt: string;
        }[];
    };
};

export default function Invoices() {
    const { invoices } = usePage<PageProps<InvoicesProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Invoices" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Invoices
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Invoice mirrors from Stripe webhooks.
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Invoice
                                    </TableHead>
                                    <TableHead>Organization</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Date
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {invoice.organization ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                tone={invoiceStatusTone(
                                                    invoice.status,
                                                )}
                                            >
                                                {invoice.status}
                                            </StatusBadge>
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {invoice.total} USD
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                invoice.createdAt,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {invoices.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No invoices yet.
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
