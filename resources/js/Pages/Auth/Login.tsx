import AuthLayout from '@/components/layouts/auth-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SocialAuthButtons from '@/features/auth/social-auth-buttons';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type LoginProps = {
    status?: string;
    canResetPassword: boolean;
    canLoginWithGoogle: boolean;
    canLoginWithGitHub: boolean;
    magicLinksEnabled: boolean;
};

export default function Login() {
    const {
        status,
        canResetPassword,
        canLoginWithGoogle,
        canLoginWithGitHub,
        magicLinksEnabled,
    } = usePage<PageProps<LoginProps>>().props;

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

            <SocialAuthButtons
                canLoginWithGoogle={canLoginWithGoogle}
                canLoginWithGitHub={canLoginWithGitHub}
            />
        </AuthLayout>
    );
}
