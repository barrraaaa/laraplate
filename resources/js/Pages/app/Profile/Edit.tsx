import AppLayout from '@/components/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import DeleteUserForm from '@/features/settings/profile/delete-user-form';
import NotificationPreferences from '@/features/settings/profile/notification-preferences';
import UpdatePasswordForm from '@/features/settings/profile/update-password-form';
import UpdateProfileInformationForm from '@/features/settings/profile/update-profile-information-form';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { KeyRound, ShieldCheck, Webhook } from 'lucide-react';

export default function Edit({
    mustVerifyEmail,
    status,
    notificationPreferences,
}: PageProps<{
    mustVerifyEmail: boolean;
    status?: string;
    notificationPreferences?: Record<string, boolean>;
}>) {
    return (
        <AppLayout>
            <Head title="Profile" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Profile settings
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Manage your account information and security.
                    </p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6">
                        <UpdatePasswordForm className="max-w-xl" />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-center justify-between p-6">
                        <div>
                            <h2 className="text-lg font-medium">API tokens</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Create personal access tokens for the JSON API.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('api-tokens.index')}>
                                <KeyRound className="mr-1.5 size-4" />
                                Manage tokens
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-center justify-between p-6">
                        <div>
                            <h2 className="text-lg font-medium">Webhooks</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Receive signed events at your endpoint.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('webhooks.index')}>
                                <Webhook className="mr-1.5 size-4" />
                                Manage webhooks
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-center justify-between p-6">
                        <div>
                            <h2 className="text-lg font-medium">
                                Two-factor authentication
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Protect your account with a TOTP authenticator
                                app.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('two-factor.index')}>
                                <ShieldCheck className="mr-1.5 size-4" />
                                Set up 2FA
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6">
                        <h2 className="text-lg font-medium">
                            Notification preferences
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Choose which events send you email — in-app
                            notifications stay on.
                        </p>
                        <div className="mt-5 max-w-xl">
                            <NotificationPreferences
                                preferences={notificationPreferences ?? {}}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-destructive/40">
                    <CardContent className="p-6">
                        <DeleteUserForm className="max-w-xl" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
