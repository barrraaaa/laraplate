import AuthLayout from '@/components/layouts/auth-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type LoginProps = {
    status?: string;
    canResetPassword: boolean;
    canLoginWithGoogle: boolean;
    magicLinksEnabled: boolean;
};

export default function Login() {
    const { status, canResetPassword, canLoginWithGoogle, magicLinksEnabled } =
        usePage<PageProps<LoginProps>>().props;

    const [mode, setMode] = useState<'password' | 'magic'>('password');

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const magic = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const sendMagicLink: FormEventHandler = (e) => {
        e.preventDefault();

        magic.post(route('auth.magic.send'), {
            onSuccess: () => magic.reset(),
        });
    };

    return (
        <AuthLayout>
            <Head title="Log in" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold tracking-tight">
                    Welcome back
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Log in to your account to continue.
                </p>
            </div>

            {status && (
                <div className="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                    {status}
                </div>
            )}

            {magicLinksEnabled && (
                <>
                    <div className="bg-muted mb-5 flex gap-1 rounded-md p-1 text-xs">
                        <button
                            type="button"
                            onClick={() => setMode('password')}
                            className={
                                mode === 'password'
                                    ? 'bg-background flex-1 rounded-sm px-2 py-1.5 font-medium shadow-sm'
                                    : 'text-muted-foreground flex-1 rounded-sm px-2 py-1.5'
                            }
                        >
                            Password
                        </button>
                        <button
                            type="button"
                            onClick={() => setMode('magic')}
                            className={
                                mode === 'magic'
                                    ? 'bg-background flex-1 rounded-sm px-2 py-1.5 font-medium shadow-sm'
                                    : 'text-muted-foreground flex-1 rounded-sm px-2 py-1.5'
                            }
                        >
                            Email link
                        </button>
                    </div>
                </>
            )}

            {mode === 'password' ? (
                <form onSubmit={submit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            autoComplete="username"
                            autoFocus
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
                            autoComplete="current-password"
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        <FormError message={errors.password} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) =>
                                setData('remember', checked === true)
                            }
                        />
                        Remember me
                    </label>

                    <div className="flex items-center justify-between gap-4">
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline"
                            >
                                Forgot your password?
                            </Link>
                        )}

                        <Button disabled={processing} className="ml-auto">
                            Log in
                        </Button>
                    </div>
                </form>
            ) : (
                <form onSubmit={sendMagicLink} className="space-y-5">
                    {magic.recentlySuccessful ? (
                        <p className="rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                            Check your inbox — the link expires in a few
                            minutes.
                        </p>
                    ) : (
                        <p className="text-muted-foreground text-sm">
                            Enter your email and we&apos;ll send you a one-click
                            login link.
                        </p>
                    )}

                    <div className="space-y-1.5">
                        <Label htmlFor="magic-email">Email</Label>
                        <Input
                            id="magic-email"
                            type="email"
                            value={magic.data.email}
                            autoFocus
                            placeholder="you@example.com"
                            onChange={(e) =>
                                magic.setData('email', e.target.value)
                            }
                        />
                        <FormError message={magic.errors.email} />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={magic.processing}
                    >
                        Send login link
                    </Button>
                </form>
            )}

            {canLoginWithGoogle && (
                <>
                    <div className="my-5 flex items-center gap-3">
                        <Separator className="flex-1" />
                        <span className="text-muted-foreground text-xs">
                            or
                        </span>
                        <Separator className="flex-1" />
                    </div>

                    <Button variant="outline" className="w-full" asChild>
                        <a href={route('auth.google.redirect')}>
                            <svg viewBox="0 0 24 24" className="mr-2 size-4">
                                <path
                                    fill="currentColor"
                                    d="M21.35 11.1H12v2.9h5.35c-.5 2.5-2.6 4.2-5.35 4.2a5.4 5.4 0 1 1 0-10.8c1.4 0 2.7.5 3.7 1.4l2.1-2.1A8.4 8.4 0 0 0 12 3.6a8.4 8.4 0 1 0 0 16.8c4.7 0 8.1-3.4 8.1-8.1 0-.6 0-1-.1-1.2z"
                                />
                            </svg>
                            Continue with Google
                        </a>
                    </Button>
                </>
            )}
        </AuthLayout>
    );
}
