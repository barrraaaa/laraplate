import AdminLayout from '@/components/layouts/admin-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { PauseCircle, PlayCircle } from 'lucide-react';

type SettingsProps = {
    app: {
        name: string;
        environment: string;
        url: string | null;
        debug: boolean;
        maintenance: boolean;
        php: string;
    };
    saas: {
        mode: string;
        features: Record<string, boolean>;
    };
    queueDriver: string | null;
    mailDriver: string | null;
};

/**
 * System settings overview (blueprint screen). Runtime configuration
 * lives in config/saas.php + .env — this screen shows its effective
 * state and exposes the maintenance-mode switch.
 */
export default function Settings() {
    const { app, saas, queueDriver, mailDriver } =
        usePage<PageProps<SettingsProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — System Settings" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        System settings
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Effective configuration — change values in
                        config/saas.php and .env, then re-deploy.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Application
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Row label="Name" value={app.name} />
                            <Row label="Environment" value={app.environment} />
                            <Row label="URL" value={app.url ?? '—'} />
                            <Row label="PHP" value={app.php} />
                            <Row
                                label="Debug mode"
                                value={
                                    <Badge
                                        variant={
                                            app.debug
                                                ? 'destructive'
                                                : 'secondary'
                                        }
                                    >
                                        {app.debug ? 'on' : 'off'}
                                    </Badge>
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Maintenance mode
                            </CardTitle>
                            <CardDescription>
                                Takes the application down for visitors (admins
                                can keep access).
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <Badge
                                    variant={
                                        app.maintenance
                                            ? 'destructive'
                                            : 'secondary'
                                    }
                                >
                                    {app.maintenance ? 'Down' : 'Up'}
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            '/admin/settings/maintenance',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {app.maintenance ? (
                                        <>
                                            <PlayCircle className="mr-1.5 size-4" />{' '}
                                            Bring up
                                        </>
                                    ) : (
                                        <>
                                            <PauseCircle className="mr-1.5 size-4" />{' '}
                                            Put into maintenance
                                        </>
                                    )}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                SaaS configuration
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Row
                                label="Mode"
                                value={
                                    <Badge variant="secondary">
                                        {saas.mode}
                                    </Badge>
                                }
                            />
                            <Row
                                label="Queue driver"
                                value={queueDriver ?? '—'}
                            />
                            <Row
                                label="Mail driver"
                                value={mailDriver ?? '—'}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Feature modules
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {Object.entries(saas.features).map(
                                    ([feature, enabled]) => (
                                        <Badge
                                            key={feature}
                                            variant={
                                                enabled ? 'default' : 'outline'
                                            }
                                        >
                                            {feature}
                                        </Badge>
                                    ),
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <span className="text-muted-foreground text-xs">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}
