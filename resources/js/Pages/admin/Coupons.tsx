import AdminLayout from '@/components/layouts/admin-layout';
import FormError from '@/components/shared/form-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

type CouponItem = {
    id: number;
    code: string;
    description: string | null;
    summary: string;
    percentOff: number | null;
    amountOff: number | null;
    stripeCouponId: string | null;
    maxRedemptions: number | null;
    redemptions: number;
    expiresAt: string | null;
    active: boolean;
};

/**
 * Coupon management (PRD 5.5 P1) — the blueprint's coupon screen: codes,
 * discount rules, caps and the redemption counter.
 */
export default function Coupons() {
    const { coupons } = usePage<PageProps<{ coupons: CouponItem[] }>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Coupons" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Coupons
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Discount codes applied at checkout. Link
                            stripe_coupon_id to a Stripe coupon when you want
                            the provider to enforce it too.
                        </p>
                    </div>
                    <CreateCouponDialog />
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Code</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead>Redemptions</TableHead>
                                    <TableHead>Expires</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="pr-6 text-right" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {coupons.map((coupon) => (
                                    <TableRow key={coupon.id}>
                                        <TableCell className="pl-6">
                                            <div className="font-mono text-sm font-medium">
                                                {coupon.code}
                                            </div>
                                            {coupon.description && (
                                                <div className="text-muted-foreground text-xs">
                                                    {coupon.description}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {coupon.summary}
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {coupon.redemptions}
                                            {coupon.maxRedemptions !== null &&
                                                ` / ${coupon.maxRedemptions}`}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs">
                                            {coupon.expiresAt
                                                ? new Date(
                                                      coupon.expiresAt,
                                                  ).toLocaleDateString()
                                                : 'Never'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Switch
                                                    checked={coupon.active}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        router.patch(
                                                            `/admin/coupons/${coupon.id}`,
                                                            { active: checked },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                                <Badge
                                                    variant={
                                                        coupon.active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {coupon.active
                                                        ? 'Active'
                                                        : 'Off'}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    router.delete(
                                                        `/admin/coupons/${coupon.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2 className="text-destructive size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {coupons.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No coupons yet.
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

function CreateCouponDialog() {
    const [open, setOpen] = useState(false);
    const [mode, setMode] = useState<'percent' | 'amount'>('percent');
    const [fields, setFields] = useState({
        code: '',
        description: '',
        percent_off: '20',
        amount_off: '1000',
        stripe_coupon_id: '',
        max_redemptions: '',
        expires_at: '',
    });
    const [error, setError] = useState<string | undefined>();

    const set = (key: string, value: string) =>
        setFields((f) => ({ ...f, [key]: value }));

    const create = () => {
        router.post(
            '/admin/coupons',
            {
                code: fields.code || undefined,
                description: fields.description || undefined,
                percent_off:
                    mode === 'percent' ? Number(fields.percent_off) : undefined,
                amount_off:
                    mode === 'amount' ? Number(fields.amount_off) : undefined,
                stripe_coupon_id: fields.stripe_coupon_id || undefined,
                max_redemptions: fields.max_redemptions
                    ? Number(fields.max_redemptions)
                    : undefined,
                expires_at: fields.expires_at || undefined,
            },
            {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
                onError: (serverErrors) => setError(serverErrors.percent_off),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus className="mr-1.5 size-4" />
                    New coupon
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create a coupon</DialogTitle>
                    <DialogDescription>
                        Codes apply at checkout; a blank code generates one
                        automatically.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="bg-muted flex gap-1 rounded-md p-1 text-xs">
                        {(['percent', 'amount'] as const).map((option) => (
                            <button
                                key={option}
                                type="button"
                                onClick={() => setMode(option)}
                                className={
                                    mode === option
                                        ? 'bg-background flex-1 rounded-sm px-2 py-1 font-medium shadow-sm'
                                        : 'text-muted-foreground flex-1 rounded-sm px-2 py-1'
                                }
                            >
                                {option === 'percent'
                                    ? 'Percent off'
                                    : 'Amount off'}
                            </button>
                        ))}
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="ccode">Code</Label>
                            <Input
                                id="ccode"
                                value={fields.code}
                                onChange={(e) => set('code', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="cvalue">
                                {mode === 'percent'
                                    ? 'Percent off'
                                    : 'Amount off (cents)'}
                            </Label>
                            <Input
                                id="cvalue"
                                type="number"
                                value={
                                    mode === 'percent'
                                        ? fields.percent_off
                                        : fields.amount_off
                                }
                                onChange={(e) =>
                                    set(
                                        mode === 'percent'
                                            ? 'percent_off'
                                            : 'amount_off',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="cdesc">Description</Label>
                        <Input
                            id="cdesc"
                            value={fields.description}
                            onChange={(e) => set('description', e.target.value)}
                        />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="cstripe">
                                Stripe coupon id (optional)
                            </Label>
                            <Input
                                id="cstripe"
                                value={fields.stripe_coupon_id}
                                onChange={(e) =>
                                    set('stripe_coupon_id', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="cmax">Max redemptions</Label>
                            <Input
                                id="cmax"
                                type="number"
                                value={fields.max_redemptions}
                                onChange={(e) =>
                                    set('max_redemptions', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="cexpires">Expires at</Label>
                        <Input
                            id="cexpires"
                            type="date"
                            value={fields.expires_at}
                            onChange={(e) => set('expires_at', e.target.value)}
                        />
                    </div>

                    <FormError message={error} />
                </div>

                <DialogFooter>
                    <Button onClick={create}>Create coupon</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
