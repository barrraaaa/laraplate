import AppLayout from '@/components/layouts/app-layout';
import StatusBadge, {
    invoiceStatusTone,
    subscriptionStatusTone,
    transactionStatusTone,
} from '@/components/shared/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import PlanCard from '@/features/billing/plan-card';
import UsageBar from '@/features/billing/usage-bar';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, CircleCheck, ExternalLink } from 'lucide-react';
import { useState } from 'react';

type InvoiceItem = {
    id: number;
    number: string;
    total: string;
    currency: string;
    status: string;
    periodEnd: string | null;
    pdfUrl: string | null;
    hostedUrl: string | null;
};

type TransactionItem = {
    id: number;
    eventType: string;
    amount: string;
    currency: string;
    status: string;
    createdAt: string;
};

type BillingProps = {
    plans: Parameters<typeof PlanCard>[0]['plan'][];
    currentPlan: {
        id: number;
        name: string;
        slug: string;
        features: Record<string, unknown>;
    };
    interval: 'monthly' | 'yearly' | null;
    subscription: {
        status: string;
        onGracePeriod: boolean;
        endsAt: string | null;
        trialEndsAt: string | null;
        onTrial: boolean;
    } | null;
    usage: { metric: string; limit: number | null; used: number }[];
    invoices: InvoiceItem[];
    transactions: TransactionItem[];
    stripeConfigured: boolean;
    canManage: boolean;
    checkoutStatus: string | null;
};

/**
 * Customer billing area (PRD 5.5): plan catalog, current subscription,
 * usage against quota, invoices and transactions.
 */
export default function Index() {
    const {
        plans,
        currentPlan,
        interval,
        subscription,
        usage,
        invoices,
        transactions,
        stripeConfigured,
        canManage,
        checkoutStatus,
    } = usePage<PageProps<BillingProps>>().props;

    const [tab, setTab] = useState<'invoices' | 'transactions'>('invoices');

    return (
        <AppLayout>
            <Head title="Billing" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Billing
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Manage your plan, usage and invoices.
                    </p>
                </div>

                {checkoutStatus === 'success' && (
                    <Alert>
                        <CircleCheck className="size-4" />
                        <AlertTitle>Payment received</AlertTitle>
                        <AlertDescription>
                            Your subscription is now active. It may take a
                            moment to reflect here.
                        </AlertDescription>
                    </Alert>
                )}

                {checkoutStatus === 'cancelled' && (
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Checkout cancelled</AlertTitle>
                        <AlertDescription>
                            No payment was processed.
                        </AlertDescription>
                    </Alert>
                )}

                {plans.some((plan) => !plan.free && !stripeConfigured) && (
                    <Alert>
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Stripe is not configured yet</AlertTitle>
                        <AlertDescription>
                            Set STRIPE_KEY and STRIPE_SECRET (and price ids in
                            config/saas.php) to enable checkout. Until then the
                            app runs on the free plan.
                        </AlertDescription>
                    </Alert>
                )}

                {subscription && (
                    <Card>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle className="text-base">
                                    Current subscription
                                </CardTitle>
                                <CardDescription>
                                    {currentPlan.name}
                                    {interval && ` · billed ${interval}`}
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-3">
                                <StatusBadge
                                    tone={subscriptionStatusTone(
                                        subscription.status,
                                    )}
                                >
                                    {subscription.status}
                                </StatusBadge>
                                {subscription.onTrial &&
                                    subscription.trialEndsAt && (
                                        <span className="text-muted-foreground text-xs">
                                            Trial ends{' '}
                                            {new Date(
                                                subscription.trialEndsAt,
                                            ).toLocaleDateString()}
                                        </span>
                                    )}
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            {usage.length > 0 && (
                                <div className="max-w-md space-y-3">
                                    {usage.map((item) => (
                                        <UsageBar
                                            key={item.metric}
                                            metric={item.metric}
                                            used={item.used}
                                            limit={item.limit}
                                        />
                                    ))}
                                </div>
                            )}

                            {canManage &&
                                subscription &&
                                !subscription.onGracePeriod && (
                                    <div className="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                router.post(
                                                    '/billing/cancel',
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Cancel subscription
                                        </Button>
                                        {stripeConfigured && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                asChild
                                            >
                                                <a href="/billing/portal">
                                                    Manage payment method
                                                    <ExternalLink className="ml-1 size-3" />
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                )}

                            {subscription.onGracePeriod && (
                                <div className="flex items-center gap-3">
                                    <p className="text-muted-foreground text-sm">
                                        This subscription is cancelled and ends{' '}
                                        {subscription.endsAt
                                            ? new Date(
                                                  subscription.endsAt,
                                              ).toLocaleDateString()
                                            : 'soon'}
                                        .
                                    </p>
                                    {canManage && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                router.post(
                                                    '/billing/resume',
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Resume subscription
                                        </Button>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                <div>
                    <h2 className="text-muted-foreground mb-3 text-sm font-semibold tracking-wide uppercase">
                        Plans
                    </h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        {plans.map((plan) => (
                            <PlanCard
                                key={plan.id}
                                plan={plan}
                                interval={interval ?? 'monthly'}
                                current={plan.slug === currentPlan.slug}
                                canManage={canManage}
                            />
                        ))}
                    </div>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">History</CardTitle>
                        <div className="bg-muted flex gap-1 rounded-md p-1 text-xs">
                            {(['invoices', 'transactions'] as const).map(
                                (option) => (
                                    <button
                                        key={option}
                                        type="button"
                                        onClick={() => setTab(option)}
                                        className={
                                            tab === option
                                                ? 'bg-background rounded-sm px-2 py-1 font-medium capitalize shadow-sm'
                                                : 'text-muted-foreground rounded-sm px-2 py-1 capitalize'
                                        }
                                    >
                                        {option}
                                    </button>
                                ),
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {tab === 'invoices' ? (
                            <Table>
                                <TableCaption>
                                    {invoices.length === 0
                                        ? 'No invoices yet.'
                                        : 'Recent invoices.'}
                                </TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Invoice</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Total
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">
                                                {invoice.number}
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
                                            <TableCell className="text-right">
                                                {invoice.total}{' '}
                                                {invoice.currency.toUpperCase()}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {invoice.hostedUrl && (
                                                    <a
                                                        href={invoice.hostedUrl}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-primary text-sm underline-offset-4 hover:underline"
                                                    >
                                                        View
                                                    </a>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <Table>
                                <TableCaption>
                                    {transactions.length === 0
                                        ? 'No transactions yet.'
                                        : 'Recent payment events.'}
                                </TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.map((transaction) => (
                                        <TableRow key={transaction.id}>
                                            <TableCell className="font-medium">
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
                                            <TableCell className="text-muted-foreground">
                                                {new Date(
                                                    transaction.createdAt,
                                                ).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {transaction.amount}{' '}
                                                {transaction.currency.toUpperCase()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
