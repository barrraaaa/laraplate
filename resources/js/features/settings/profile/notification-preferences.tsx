import { Switch } from '@/components/ui/switch';
import { router } from '@inertiajs/react';
import { useState } from 'react';

type Preferences = {
    payment_success?: boolean;
    payment_failure?: boolean;
    product_news?: boolean;
};

const OPTIONS: { key: keyof Preferences; title: string; body: string }[] = [
    {
        key: 'payment_success',
        title: 'Payment receipts',
        body: 'Email me after successful charges.',
    },
    {
        key: 'payment_failure',
        title: 'Payment failures',
        body: 'Email me when a payment cannot be processed.',
    },
    {
        key: 'product_news',
        title: 'Product updates',
        body: 'Occasional product news and changelog notes.',
    },
];

/**
 * Notification preferences (PRD 5.7 P1): per-user email channels —
 * in-app notifications always stay on.
 */
export default function NotificationPreferences({
    preferences,
}: {
    preferences: Preferences;
}) {
    const [values, setValues] = useState<Preferences>(preferences);

    const toggle = (key: keyof Preferences) => {
        const next = { ...values, [key]: !(values[key] ?? true) };
        setValues(next);

        router.patch(
            '/profile/notification-preferences',
            { ...next },
            { preserveScroll: true },
        );
    };

    return (
        <div className="divide-y">
            {OPTIONS.map((option) => (
                <div
                    key={option.key}
                    className="flex items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0"
                >
                    <div>
                        <div className="text-sm font-medium">
                            {option.title}
                        </div>
                        <div className="text-muted-foreground mt-0.5 text-xs">
                            {option.body}
                        </div>
                    </div>
                    <Switch
                        checked={values[option.key] ?? true}
                        onCheckedChange={() => toggle(option.key)}
                    />
                </div>
            ))}
        </div>
    );
}
