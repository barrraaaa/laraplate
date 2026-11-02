import AppLayout from '@/components/layouts/app-layout';
import FormError from '@/components/shared/form-error';
import StatusBadge from '@/components/shared/status-badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { KeyRound, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';

type EndpointItem = {
    id: number;
    url: string;
    events: string[];
    active: boolean;
    deliveriesCount: number;
    createdAt: string;
};

type DeliveryItem = {
    id: number;
    endpoint: string | null;
    event: string;
    attemptCount: number;
    responseCode: number | null;
    delivered: boolean;
    createdAt: string;
};

type WebhooksProps = {
    endpoints: EndpointItem[];
    deliveries: DeliveryItem[];
    eventCatalog: Record<string, string>;
    canManage: boolean;
};

/**
 * Customer webhook management (PRD 5.8 P1): per-organization endpoints
 * over the fixed event catalog, with the delivery log and manual retries.
 */
export default function Webhooks() {
    const { endpoints, deliveries, eventCatalog, canManage } =
        usePage<PageProps<WebhooksProps>>().props;

    return (
        <AppLayout>
            <Head title="Webhooks" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Webhooks
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Receive signed events from{' '}
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'} at
                            your URL.
                        </p>
                    </div>
                    {canManage && (
                        <CreateEndpointDialog catalog={eventCatalog} />
                    )}
                </div>

                {endpoints.length === 0 && (
                    <Card className="border-dashed">
                        <CardContent className="text-muted-foreground py-12 text-center text-sm">
                            No endpoints yet — register one to start receiving
                            events.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-4">
                    {endpoints.map((endpoint) => (
                        <Card key={endpoint.id}>
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div className="min-w-0">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <span className="truncate">
                                            {endpoint.url}
                                        </span>
                                        <Badge
                                            variant={
                                                endpoint.active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {endpoint.active
                                                ? 'Active'
                                                : 'Paused'}
                                        </Badge>
                                    </CardTitle>
                                    <CardDescription>
                                        {endpoint.deliveriesCount} deliveries ·
                                        created{' '}
                                        {new Date(
                                            endpoint.createdAt,
                                        ).toLocaleDateString()}
                                    </CardDescription>
                                </div>
                                {canManage && (
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Rotate secret"
                                            onClick={() =>
                                                router.post(
                                                    `/settings/webhooks/${endpoint.id}/regenerate-secret`,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <KeyRound className="size-4" />
                                        </Button>
                                        <Switch
                                            checked={endpoint.active}
                                            onCheckedChange={(checked) =>
                                                router.patch(
                                                    `/settings/webhooks/${endpoint.id}`,
                                                    { active: checked },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="text-destructive size-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        Delete this endpoint?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Delivery history is
                                                        removed with it.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>
                                                        Cancel
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction
                                                        onClick={() =>
                                                            router.delete(
                                                                `/settings/webhooks/${endpoint.id}`,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                    >
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                )}
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-1.5">
                                    {endpoint.events.map((event) => (
                                        <Badge
                                            key={event}
                                            variant="outline"
                                            className="font-mono text-[10px]"
                                        >
                                            {event}
                                        </Badge>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Recent deliveries
                        </CardTitle>
                        <CardDescription>
                            One row per event — retries happen automatically, or
                            push retry manually.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Endpoint
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
                                {deliveries.map((delivery) => (
                                    <TableRow key={delivery.id}>
                                        <TableCell className="text-muted-foreground max-w-48 truncate pl-6 text-xs">
                                            {delivery.endpoint ?? '—'}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">
                                            {delivery.event}
                                        </TableCell>
                                        <TableCell>
                                            {delivery.delivered ? (
                                                <StatusBadge tone="success">
                                                    delivered
                                                </StatusBadge>
                                            ) : (
                                                <div className="flex items-center gap-2">
                                                    <StatusBadge
                                                        tone={
                                                            delivery.responseCode
                                                                ? 'destructive'
                                                                : 'warning'
                                                        }
                                                    >
                                                        {delivery.responseCode ??
                                                            'pending'}
                                                    </StatusBadge>
                                                    {canManage && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-6"
                                                            title="Retry now"
                                                            onClick={() =>
                                                                router.post(
                                                                    `/settings/webhooks/${delivery.id}/retry`,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <RefreshCw className="size-3.5" />
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
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
                                {deliveries.length === 0 && (
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
        </AppLayout>
    );
}

function CreateEndpointDialog({
    catalog,
}: {
    catalog: Record<string, string>;
}) {
    const [open, setOpen] = useState(false);
    const [url, setUrl] = useState('');
    const [selected, setSelected] = useState<string[]>([]);
    const [error, setError] = useState<string | undefined>();

    const toggle = (event: string) => {
        setSelected((current) =>
            current.includes(event)
                ? current.filter((item) => item !== event)
                : [...current, event],
        );
    };

    const create = () => {
        router.post(
            '/settings/webhooks',
            { url, events: selected },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setUrl('');
                    setSelected([]);
                    setOpen(false);
                },
                onError: (serverErrors) => setError(serverErrors.url),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus className="mr-1.5 size-4" />
                    Add endpoint
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Register an endpoint</DialogTitle>
                    <DialogDescription>
                        Every delivery is signed with a per-endpoint HMAC
                        secret.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="wh-url">Endpoint URL</Label>
                        <Input
                            id="wh-url"
                            value={url}
                            placeholder="https://api.example.com/hooks"
                            onChange={(e) => setUrl(e.target.value)}
                        />
                        <FormError message={error} />
                    </div>

                    <div className="space-y-2">
                        <Label>Events</Label>
                        <div className="grid gap-1.5">
                            {Object.entries(catalog).map(
                                ([event, description]) => (
                                    <label
                                        key={event}
                                        className="flex items-start gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={selected.includes(event)}
                                            onCheckedChange={() =>
                                                toggle(event)
                                            }
                                        />
                                        <span>
                                            <span className="font-mono text-xs">
                                                {event}
                                            </span>
                                            <span className="text-muted-foreground block text-xs">
                                                {description}
                                            </span>
                                        </span>
                                    </label>
                                ),
                            )}
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        onClick={create}
                        disabled={url.trim() === '' || selected.length === 0}
                    >
                        Create endpoint
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
