import FormError from '@/components/shared/form-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { Check, Minus } from 'lucide-react';
import { useState } from 'react';

export type PlanItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    free: boolean;
    features: {
        features?: Record<string, boolean>;
        quotas?: Record<string, number | null>;
    };
};

/**
 * One plan in the billing catalog (PRD 5.5). Paid plans start a Stripe
 * checkout for the chosen interval; free plans carry no action.
 */
export default function PlanCard({
    plan,
    interval,
    current,
    canManage,
}: {
    plan: PlanItem;
    interval: 'monthly' | 'yearly';
    current: boolean;
    canManage: boolean;
}) {
    const [billingInterval, setBillingInterval] = useState<
        'monthly' | 'yearly'
    >(interval);
    const [coupon, setCoupon] = useState('');
    const [couponError, setCouponError] = useState<string | undefined>();

    const featureFlags = Object.entries(plan.features.features ?? {});
    const quotas = Object.entries(plan.features.quotas ?? {});

    const checkout = () => {
        router.post(
            '/billing/checkout',
            {
                plan_id: plan.id,
                interval: billingInterval,
                coupon_code: coupon || undefined,
            },
            {
                preserveScroll: true,
                onError: (serverErrors) => setCouponError(serverErrors.coupon),
            },
        );
    };

    return (
        <Card
            className={
                current ? 'border-primary/50 ring-primary/20 ring-1' : ''
            }
        >
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-base">{plan.name}</CardTitle>
                    {current && <Badge>Current plan</Badge>}
                </div>
                {plan.description && (
                    <CardDescription>{plan.description}</CardDescription>
                )}
            </CardHeader>

            <CardContent className="space-y-3 text-sm">
                <ul className="space-y-1.5">
                    {featureFlags.map(([feature, enabled]) => (
                        <li key={feature} className="flex items-center gap-2">
                            {enabled ? (
                                <Check className="size-4 text-emerald-600 dark:text-emerald-400" />
                            ) : (
                                <Minus className="text-muted-foreground size-4" />
                            )}
                            <span
                                className={
                                    enabled ? '' : 'text-muted-foreground'
                                }
                            >
                                {labelFor(feature)}
                            </span>
                        </li>
                    ))}
                    {quotas.map(([metric, limit]) => (
                        <li
                            key={metric}
                            className="text-muted-foreground flex items-center gap-2"
                        >
                            <Check className="text-muted-foreground/50 size-4" />
                            {labelFor(metric)}:{' '}
                            {limit === null ? 'Unlimited' : limit}
                        </li>
                    ))}
                </ul>

                {!plan.free && (
                    <div className="bg-muted flex gap-1 rounded-md p-1 text-xs">
                        {(['monthly', 'yearly'] as const).map((option) => (
                            <button
                                key={option}
                                type="button"
                                onClick={() => setBillingInterval(option)}
                                className={
                                    billingInterval === option
                                        ? 'bg-background flex-1 rounded-sm px-2 py-1 font-medium capitalize shadow-sm'
                                        : 'text-muted-foreground flex-1 rounded-sm px-2 py-1 capitalize'
                                }
                            >
                                {option}
                            </button>
                        ))}
                    </div>
                )}

                {!plan.free && (
                    <div className="space-y-1">
                        <Input
                            value={coupon}
                            placeholder="Coupon code (optional)"
                            className="h-8 text-xs"
                            onChange={(e) => setCoupon(e.target.value)}
                        />
                        <FormError message={couponError} />
                    </div>
                )}
            </CardContent>

            <CardFooter>
                {plan.free ? (
                    <Button variant="outline" disabled className="w-full">
                        {current ? 'Included' : 'Free forever'}
                    </Button>
                ) : current ? (
                    <Button variant="outline" disabled className="w-full">
                        Active plan
                    </Button>
                ) : (
                    <Button
                        className="w-full"
                        disabled={!canManage}
                        onClick={checkout}
                    >
                        {canManage ? `Choose ${plan.name}` : 'Requires admin'}
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}

function labelFor(key: string): string {
    return key
        .split('.')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
