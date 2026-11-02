import AppLayout from '@/components/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

type RoleItem = {
    id: number;
    name: string;
    baseline: boolean;
    permissions: string[];
    assignedTo: number;
};

type MemberItem = {
    id: number;
    name: string;
    email: string;
    customRoleId: number | null;
};

type RolesProps = {
    organization: { id: number; name: string };
    roles: RoleItem[];
    permissionCatalog: string[];
    members: MemberItem[];
};

/**
 * Custom organization roles (PRD 5.4 P1): owners/admins compose roles
 * from the permission catalog and assign them to members.
 */
export default function Roles() {
    const { organization, roles, permissionCatalog, members } =
        usePage<PageProps<RolesProps>>().props;

    return (
        <AppLayout>
            <Head title={`${organization.name} — Roles`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Custom roles
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Compose roles from the permission catalog and assign
                            them to members of {organization.name}.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/organizations/${organization.id}`}>
                                Back to settings
                            </Link>
                        </Button>
                        <CreateRoleDialog
                            organizationId={organization.id}
                            catalog={permissionCatalog}
                        />
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {roles.map((role) => (
                        <Card key={role.id}>
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        {displayName(role.name)}
                                        {role.baseline && (
                                            <Badge variant="secondary">
                                                Baseline
                                            </Badge>
                                        )}
                                    </CardTitle>
                                    <CardDescription>
                                        {role.permissions.length} permissions ·
                                        assigned to {role.assignedTo}
                                    </CardDescription>
                                </div>
                                {!role.baseline && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            router.delete(
                                                `/organizations/${organization.id}/roles/${role.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash2 className="text-destructive size-4" />
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex flex-wrap gap-1.5">
                                    {role.permissions.map((permission) => (
                                        <Badge
                                            key={permission}
                                            variant="outline"
                                            className="font-mono text-[10px]"
                                        >
                                            {permission}
                                        </Badge>
                                    ))}
                                </div>

                                {!role.baseline && (
                                    <EditRolePermissions
                                        organizationId={organization.id}
                                        role={role}
                                        catalog={permissionCatalog}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {roles.some((role) => !role.baseline) && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Assign to members
                            </CardTitle>
                            <CardDescription>
                                Custom roles override the baseline permission
                                mirror — the curated owner/admin/member/viewer
                                hierarchy keeps governing policies.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex items-center justify-between gap-4"
                                >
                                    <div>
                                        <div className="text-sm font-medium">
                                            {member.name}
                                        </div>
                                        <div className="text-muted-foreground text-xs">
                                            {member.email}
                                        </div>
                                    </div>
                                    <AssignRoleSelect
                                        organizationId={organization.id}
                                        member={member}
                                        roles={roles.filter(
                                            (role) => !role.baseline,
                                        )}
                                    />
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

function displayName(name: string): string {
    return name.replace(/^custom-/, '');
}

function CreateRoleDialog({
    organizationId,
    catalog,
}: {
    organizationId: number;
    catalog: string[];
}) {
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [selected, setSelected] = useState<string[]>([]);

    const toggle = (permission: string) => {
        setSelected((current) =>
            current.includes(permission)
                ? current.filter((item) => item !== permission)
                : [...current, permission],
        );
    };

    const create = () => {
        router.post(
            `/organizations/${organizationId}/roles`,
            { name, permissions: selected },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setName('');
                    setSelected([]);
                    setOpen(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus className="mr-1.5 size-4" />
                    New role
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Create a custom role</DialogTitle>
                    <DialogDescription>
                        Pick a name and grant permissions from the catalog.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="role-name">Role name</Label>
                        <Input
                            id="role-name"
                            value={name}
                            placeholder="Content editor"
                            onChange={(e) => setName(e.target.value)}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label>Permissions</Label>
                        <div className="grid gap-1.5 sm:grid-cols-2">
                            {catalog.map((permission) => (
                                <label
                                    key={permission}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={selected.includes(permission)}
                                        onCheckedChange={() =>
                                            toggle(permission)
                                        }
                                    />
                                    <span className="font-mono text-xs">
                                        {permission}
                                    </span>
                                </label>
                            ))}
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        onClick={create}
                        disabled={name.trim() === '' || selected.length === 0}
                    >
                        Create role
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function EditRolePermissions({
    organizationId,
    role,
    catalog,
}: {
    organizationId: number;
    role: RoleItem;
    catalog: string[];
}) {
    const [selected, setSelected] = useState<string[]>(role.permissions);

    const toggle = (permission: string) => {
        setSelected((current) =>
            current.includes(permission)
                ? current.filter((item) => item !== permission)
                : [...current, permission],
        );
    };

    return (
        <div className="space-y-2 rounded-lg border p-3">
            <Label className="text-xs">Permissions</Label>
            <div className="grid gap-1.5 sm:grid-cols-2">
                {catalog.map((permission) => (
                    <label
                        key={permission}
                        className="flex items-center gap-2 text-sm"
                    >
                        <Checkbox
                            checked={selected.includes(permission)}
                            onCheckedChange={() => toggle(permission)}
                        />
                        <span className="font-mono text-xs">{permission}</span>
                    </label>
                ))}
            </div>
            <Button
                size="sm"
                variant="outline"
                onClick={() =>
                    router.patch(
                        `/organizations/${organizationId}/roles/${role.id}`,
                        { permissions: selected },
                        { preserveScroll: true },
                    )
                }
            >
                Save permissions
            </Button>
        </div>
    );
}

function AssignRoleSelect({
    organizationId,
    member,
    roles,
}: {
    organizationId: number;
    member: MemberItem;
    roles: RoleItem[];
}) {
    const assign = (roleId: string) => {
        router.post(
            `/organizations/${organizationId}/members/${member.id}/role-assignment`,
            { role_id: roleId === 'none' ? null : roleId },
            { preserveScroll: true },
        );
    };

    return (
        <Select
            value={member.customRoleId ? String(member.customRoleId) : 'none'}
            onValueChange={assign}
        >
            <SelectTrigger className="h-8 w-44">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="none">Baseline role</SelectItem>
                {roles.map((role) => (
                    <SelectItem key={role.id} value={String(role.id)}>
                        {displayName(role.name)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
