import AuthLayout from '@/components/layouts/auth-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout>
            <Head title="Register" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold tracking-tight">
                    Create your account
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Get started with{' '}
                    {import.meta.env.VITE_APP_NAME || 'Laraplate'} in under a
                    minute.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        value={data.name}
                        autoComplete="name"
                        autoFocus
                        required
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <FormError message={errors.name} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        required
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <FormError message={errors.email} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        required
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <FormError message={errors.password} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="password_confirmation">
                        Confirm Password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        required
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    <FormError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center justify-between gap-4">
                    <Link
                        href={route('login')}
                        className="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline"
                    >
                        Already registered?
                    </Link>

                    <Button disabled={processing}>Register</Button>
                </div>
            </form>
        </AuthLayout>
    );
}
