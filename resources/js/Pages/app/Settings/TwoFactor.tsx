import AppLayout from '@/components/layouts/app-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ShieldCheck, ShieldOff } from 'lucide-react';
import { useState } from 'react';

type TwoFactorProps = {
    enabled: boolean;
    pending: boolean;
    qrCodeSvg: string | null;
    secret: string | null;
    recoveryCodes: string[];
    status: string | null;
};

/**
 * Two-factor settings: enable (QR + confirm), regenerate recovery codes,
 * disable with a current code.
 */
export default function TwoFactor() {
    const { enabled, pending, qrCodeSvg, secret, recoveryCodes } =
        usePage<PageProps<TwoFactorProps>>().props;

    const [confirmCode, setConfirmCode] = useState('');
    const [confirmError, setConfirmError] = useState<string | undefined>();

    const [disableCode, setDisableCode] = useState('');
    const [disableError, setDisableError] = useState<string | undefined>();

    const confirm = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/settings/two-factor/confirm',
            { code: confirmCode },
            {
                preserveScroll: true,
                onError: (serverErrors) => setConfirmError(serverErrors.code),
            },
        );
    };

    const disable = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/settings/two-factor/disable',
            { code: disableCode },
            {
                preserveScroll: true,
                onError: (serverErrors) => setDisableError(serverErrors.code),
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Two-Factor Authentication" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Two-factor authentication
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Add a second step to sign-in with a TOTP authenticator
                        app.
                    </p>
                </div>

                {recoveryCodes.length > 0 && (
                    <Card className="border-emerald-600/40 dark:border-emerald-400/40">
                        <CardContent className="p-5">
                            <p className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                Save these recovery codes — each works once and
                                they are shown only now:
                            </p>
                            <div className="mt-3 grid grid-cols-2 gap-2">
                                {recoveryCodes.map((code) => (
                                    <code
                                        key={code}
                                        className="bg-muted rounded px-2 py-1 font-mono text-xs"
                                    >
                                        {code}
                                    </code>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {pending && qrCodeSvg ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldCheck className="size-4" />
                                Scan the QR code
                            </CardTitle>
                            <CardDescription>
                                Add it to Google Authenticator, 1Password or any
                                TOTP app — or enter the secret manually.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div
                                className="inline-block rounded-lg bg-white p-3"
                                dangerouslySetInnerHTML={{ __html: qrCodeSvg }}
                            />
                            <div className="text-muted-foreground text-xs">
                                Secret:{' '}
                                <code className="font-mono">{secret}</code>
                            </div>

                            <form
                                onSubmit={confirm}
                                className="max-w-sm space-y-3"
                            >
                                <div className="space-y-1.5">
                                    <Label htmlFor="confirm-code">
                                        Verification code
                                    </Label>
                                    <Input
                                        id="confirm-code"
                                        inputMode="numeric"
                                        placeholder="123456"
                                        value={confirmCode}
                                        onChange={(e) =>
                                            setConfirmCode(e.target.value)
                                        }
                                    />
                                    <FormError message={confirmError} />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={confirmCode.length !== 6}
                                >
                                    Confirm and enable
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                ) : enabled ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldCheck className="size-4 text-emerald-600 dark:text-emerald-400" />
                                Enabled
                            </CardTitle>
                            <CardDescription>
                                Two-factor protects your account. Regenerate
                                codes any time, or disable with a current code.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            '/settings/two-factor/regenerate',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Regenerate recovery codes
                                </Button>
                            </div>

                            <form
                                onSubmit={disable}
                                className="max-w-sm space-y-3"
                            >
                                <div className="space-y-1.5">
                                    <Label htmlFor="disable-code">
                                        Current code
                                    </Label>
                                    <Input
                                        id="disable-code"
                                        inputMode="numeric"
                                        placeholder="123456 or recovery code"
                                        value={disableCode}
                                        onChange={(e) =>
                                            setDisableCode(e.target.value)
                                        }
                                    />
                                    <FormError message={disableError} />
                                </div>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={disableCode.trim() === ''}
                                >
                                    Disable two-factor
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldOff className="text-muted-foreground size-4" />
                                Not enabled
                            </CardTitle>
                            <CardDescription>
                                Enroll your authenticator app — it takes about a
                                minute.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/settings/two-factor/enable',
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Set up two-factor
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
