import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuth } from '@/hooks/use-organization';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

/**
 * Name/email settings with verification resend.
 */
export default function UpdateProfileInformationForm({
    mustVerifyEmail,
    status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const { user } = useAuth();

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            avatar: null as File | null,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium">Profile Information</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    Update your account&apos;s profile information and email
                    address.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="flex items-center gap-4">
                    {user.avatar_url ? (
                        <img
                            src={user.avatar_url}
                            alt={user.name}
                            className="bg-muted size-14 rounded-full object-cover"
                        />
                    ) : (
                        <div className="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-full text-lg font-semibold">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                    )}
                    <div className="flex-1 space-y-1.5">
                        <Label htmlFor="avatar">Avatar</Label>
                        <Input
                            id="avatar"
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) {
                                    setData('avatar', file);
                                }
                            }}
                        />
                        <FormError message={errors.avatar} />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
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
                        value={data.email}
                        autoComplete="username"
                        required
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <FormError message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div className="space-y-2">
                        <p className="text-muted-foreground text-sm">
                            Your email address is unverified.{' '}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="text-foreground underline underline-offset-4"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                A new verification link has been sent to your
                                email address.
                            </p>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <Button disabled={processing}>Save</Button>

                    {recentlySuccessful && (
                        <span className="text-muted-foreground text-sm">
                            Saved.
                        </span>
                    )}
                </div>
            </form>
        </section>
    );
}
