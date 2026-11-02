import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type StatusTone =
    'success' | 'warning' | 'destructive' | 'neutral' | 'info';

const TONES: Record<StatusTone, string> = {
    success:
        'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    warning:
        'border-amber-600/30 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    destructive:
        'border-red-600/30 bg-red-500/10 text-red-700 dark:text-red-400',
    neutral: 'border-border bg-muted text-muted-foreground',
    info: 'border-sky-600/30 bg-sky-500/10 text-sky-700 dark:text-sky-400',
};

/**
 * Status badge with semantic tones — one shared control for invoices,
 * transactions, subscriptions and any other state column, so "paid"
 * always reads green everywhere.
 */
export default function StatusBadge({
    tone,
    children,
    className,
}: {
    tone: StatusTone;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn('capitalize', TONES[tone], className)}
        >
            {children}
        </Badge>
    );
}

/**
 * Semantic tone mappings per domain — reuse instead of inventing
 * per-page colors.
 */
export const invoiceStatusTone = (status: string): StatusTone => {
    switch (status) {
        case 'paid':
            return 'success';
        case 'open':
        case 'pending':
            return 'warning';
        case 'uncollectible':
        case 'void':
            return 'destructive';
        default:
            return 'neutral';
    }
};

export const transactionStatusTone = (status: string): StatusTone => {
    switch (status) {
        case 'succeeded':
            return 'success';
        case 'failed':
            return 'destructive';
        case 'pending':
        case 'action_required':
            return 'warning';
        default:
            return 'neutral';
    }
};

export const subscriptionStatusTone = (status: string): StatusTone => {
    switch (status) {
        case 'active':
        case 'trialing':
            return 'success';
        case 'past_due':
        case 'incomplete':
            return 'warning';
        case 'canceled':
        case 'incomplete_expired':
        case 'unpaid':
            return 'neutral';
        default:
            return 'neutral';
    }
};
