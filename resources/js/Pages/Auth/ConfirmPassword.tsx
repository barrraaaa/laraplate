import AuthLayout from '@/components/layouts/auth-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout>
            <Head title="Confirm Password" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold tracking-tight">
                    Confirm your password
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    This is a secure area of the application. Please confirm
                    your password before continuing.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        autoFocus
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <FormError message={errors.password} />
                </div>

                <div className="flex justify-end">
                    <Button disabled={processing}>Confirm</Button>
                </div>
            </form>
        </AuthLayout>
    );
}
