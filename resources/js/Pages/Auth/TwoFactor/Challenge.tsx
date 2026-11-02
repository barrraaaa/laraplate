import AuthLayout from '@/components/layouts/auth-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

/**
 * 2FA challenge shown after login until the TOTP code (or a recovery
 * code) verifies.
 */
export default function Challenge() {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('two-factor.verify'));
    };

    return (
        <AuthLayout>
            <Head title="Two-Factor Challenge" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold tracking-tight">
                    Two-factor authentication
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Enter the 6-digit code from your authenticator app, or one
                    of your recovery codes.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="code">Code</Label>
                    <Input
                        id="code"
                        inputMode="numeric"
                        autoFocus
                        autoComplete="one-time-code"
                        placeholder="123456"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                    />
                    <FormError message={errors.code} />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    Verify
                </Button>
            </form>
        </AuthLayout>
    );
}
