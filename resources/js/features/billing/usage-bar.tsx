import { cn } from '@/lib/utils';

/**
 * Usage vs queried quota bar (PRD 5.6: usage display in the customer area).
 */
export default function UsageBar({
    metric,
    used,
    limit,
}: {
    metric: string;
    used: number;
    limit: number | null;
}) {
    const percentage =
        limit === null || limit === 0
            ? 0
            : Math.min(100, Math.round((used / limit) * 100));
    const nearLimit = limit !== null && limit > 0 && used >= limit;

    return (
        <div>
            <div className="mb-1 flex items-center justify-between text-xs">
                <span className="font-medium capitalize">
                    {metric.split('.').join(' ')}
                </span>
                <span
                    className={cn(
                        'text-muted-foreground',
                        nearLimit && 'text-destructive font-semibold',
                    )}
                >
                    {used} / {limit === null ? '∞' : limit}
                </span>
            </div>
            <div className="bg-muted h-2 overflow-hidden rounded-full">
                <div
                    className={cn(
                        'h-full rounded-full transition-all',
                        nearLimit ? 'bg-destructive' : 'bg-primary',
                    )}
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
}
