import AppLayout from '@/components/layouts/app-layout';
import FormError from '@/components/shared/form-error';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
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
import InviteMemberDialog from '@/features/teams/invite-member-dialog';
import MemberRow, { type MemberItem } from '@/features/teams/member-row';
import TransferOwnershipDialog from '@/features/teams/transfer-ownership-dialog';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export type InvitationItem = {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    sent_at: string;
};

type SettingsProps = {
    organization: {
        id: number;
        name: string;
        slug: string | null;
        personal: boolean;
    };
    members: MemberItem[];
    invitations: InvitationItem[];
    can: {
        manage: boolean;
        manageMembers: boolean;
        transfer: boolean;
        delete: boolean;
    };
};

/**
 * Organization settings (PRD 5.3): profile, members, invitations and the
 * archive danger-zone — all server-side authorized.
 */
export default function Settings() {
    const { organization, members, invitations, can } =
        usePage<PageProps<SettingsProps>>().props;

    const [name, setName] = useState(organization.name);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | undefined>();

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);

        router.patch(
            `/organizations/${organization.id}`,
            { name },
            {
                preserveScroll: true,
                onSuccess: () => setSaving(false),
                onError: (serverErrors) => {
                    setSaving(false);
                    setError(serverErrors.name);
                },
            },
        );
    };

    const archive = () => {
        router.delete(`/organizations/${organization.id}`);
    };

    return (
        <AppLayout>
            <Head title={organization.name} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {organization.name}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Organization settings and members.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Organization
                        </CardTitle>
                        <CardDescription>Name and public slug.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={save} className="max-w-xl space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    disabled={!can.manage}
                                    onChange={(e) => setName(e.target.value)}
                                />
                                <FormError message={error} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={organization.slug ?? ''}
                                    disabled
                                />
                            </div>
                            {can.manage && (
                                <Button type="submit" disabled={saving}>
                                    Save changes
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle className="text-base">Members</CardTitle>
                            <CardDescription>
                                {members.length} member
                                {members.length === 1 ? '' : 's'} · roles
                                control what each member can do.
                            </CardDescription>
                        </div>
                        <div className="flex items-center gap-2">
                            {can.manageMembers && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={`/organizations/${organization.id}/roles`}
                                    >
                                        Custom roles
                                    </Link>
                                </Button>
                            )}
                            {can.transfer && members.length > 1 && (
                                <TransferOwnershipDialog
                                    organizationId={organization.id}
                                    members={members}
                                />
                            )}
                            {can.manageMembers && (
                                <InviteMemberDialog
                                    organizationId={organization.id}
                                />
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="divide-y">
                            {members.map((member) => (
                                <MemberRow
                                    key={member.id}
                                    member={member}
                                    organizationId={organization.id}
                                    canManage={can.manageMembers}
                                />
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {can.manageMembers && invitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Pending invitations
                            </CardTitle>
                            <CardDescription>
                                Invitations expire automatically and can be
                                re-sent.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="divide-y">
                                {invitations.map((invitation) => (
                                    <div
                                        key={invitation.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <div className="truncate text-sm font-medium">
                                                {invitation.email}
                                            </div>
                                            <div className="text-muted-foreground text-xs capitalize">
                                                {invitation.role} · expires{' '}
                                                {new Date(
                                                    invitation.expires_at,
                                                ).toLocaleDateString()}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        `/organizations/${organization.id}/invitations/${invitation.id}/resend`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Resend
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    router.delete(
                                                        `/organizations/${organization.id}/invitations/${invitation.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Revoke
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {can.delete && (
                    <Card className="border-destructive/40">
                        <CardHeader>
                            <CardTitle className="text-destructive text-base">
                                Danger zone
                            </CardTitle>
                            <CardDescription>
                                Archiving removes the organization and members
                                lose access. This can be undone by support only.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="destructive">
                                        Archive organization
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>
                                            Archive {organization.name}?
                                        </AlertDialogTitle>
                                        <AlertDialogDescription>
                                            All members will immediately lose
                                            access to this organization and its
                                            data. This action cannot be undone
                                            from the dashboard.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>
                                            Cancel
                                        </AlertDialogCancel>
                                        <AlertDialogAction
                                            onClick={archive}
                                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                        >
                                            Archive organization
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
