import AdminLayout from '@/components/layouts/admin-layout';
import { Card, CardContent } from '@/components/ui/card';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Activity, CircleCheck, TriangleAlert, XCircle } from 'lucide-react';

type HealthProps = {
    checks: {
        name: string;
        label: string;
        status: 'ok' | 'warning' | 'failed' | 'crashed' | 'skipped';
        summary: string;
        checkedAt: string;
    }[];
};

const STATUS_STYLES: Record<
    HealthProps['checks'][number]['status'],
    { icon: typeof Activity; className: string }
> = {
    ok: {
        icon: CircleCheck,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    warning: {
        icon: TriangleAlert,
        className: 'text-amber-600 dark:text-amber-400',
    },
    failed: { icon: XCircle, className: 'text-destructive' },
    crashed: { icon: XCircle, className: 'text-destructive' },
    skipped: { icon: TriangleAlert, className: 'text-muted-foreground' },
};

/**
 * System health per the blueprint (runtime, database, cache, queue,
 * storage) — mirrored from the configured spatie/laravel-health checks.
 */
export default function SystemHealth() {
    const { checks } = usePage<PageProps<HealthProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — System Health" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        System health
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Fresh diagnostics for the application runtime and its
                        dependencies.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {checks.map((check) => {
                        const style =
                            STATUS_STYLES[check.status] ??
                            STATUS_STYLES.skipped;
                        const Icon = style.icon;

                        return (
                            <Card key={check.name}>
                                <CardContent className="p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <Icon
                                                    className={`size-4 ${style.className}`}
                                                />
                                                <span className="text-sm font-medium">
                                                    {check.label}
                                                </span>
                                            </div>
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {check.summary}
                                            </p>
                                        </div>
                                        <span className="text-muted-foreground shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase">
                                            {check.status}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground/70 mt-3 text-[11px]">
                                        Checked{' '}
                                        {new Date(
                                            check.checkedAt,
                                        ).toLocaleString()}
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}

                    {checks.length === 0 && (
                        <Card className="md:col-span-2">
                            <CardContent className="text-muted-foreground py-10 text-center text-sm">
                                No health results recorded yet — run the checks
                                first.
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
