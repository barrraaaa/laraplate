import { cn } from '@/lib/utils';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * Standard empty state used across the customer app and the admin panel —
 * build once, reuse everywhere.
 */
export default function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-16 text-center',
                className,
            )}
        >
            <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                <Icon className="text-muted-foreground size-6" />
            </div>

            <h3 className="mt-4 text-sm font-semibold">{title}</h3>

            {description && (
                <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                    {description}
                </p>
            )}

            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
