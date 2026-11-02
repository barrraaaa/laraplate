import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm({
    className = '',
}: {
    className?: string;
}) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium">Update Password</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    Ensure your account is using a long, random password to stay
                    secure.
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-6">
                <div className="space-y-1.5">
                    <Label htmlFor="current_password">Current Password</Label>
                    <Input
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        autoComplete="current-password"
                        type="password"
                        onChange={(e) =>
                            setData('current_password', e.target.value)
                        }
                    />
                    <FormError message={errors.current_password} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="password">New Password</Label>
                    <Input
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        autoComplete="new-password"
                        type="password"
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
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        type="password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    <FormError message={errors.password_confirmation} />
                </div>

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
