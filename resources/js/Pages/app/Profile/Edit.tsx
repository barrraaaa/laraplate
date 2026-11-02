import AppLayout from '@/components/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import DeleteUserForm from '@/features/settings/profile/delete-user-form';
import UpdatePasswordForm from '@/features/settings/profile/update-password-form';
import UpdateProfileInformationForm from '@/features/settings/profile/update-profile-information-form';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
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

                <Card className="border-destructive/40">
                    <CardContent className="p-6">
                        <DeleteUserForm className="max-w-xl" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
