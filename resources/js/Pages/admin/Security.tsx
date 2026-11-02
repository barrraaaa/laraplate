import AdminLayout from '@/components/layouts/admin-layout';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

type SecurityProps = {
    roles: {
        id: number;
        name: string;
        organization: string;
        permissions: string[];
        assignedTo: number;
    }[];
    catalog: Record<string, string[]>;
};

/**
 * Roles & permissions overview (blueprint Security screen): every
 * organization-scoped role, its permission set and assignment count,
 * plus the seeded catalog per baseline role.
 */
export default function Security() {
    const { roles, catalog } = usePage<PageProps<SecurityProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Security" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Roles & permissions
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Organization-scoped roles (spatie) with their permission
                        sets — baseline roles mirror the catalog in
                        config/saas.php.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ShieldCheck className="text-muted-foreground size-4" />
                            Baseline role catalog
                        </CardTitle>
                        <CardDescription>
                            Permissions each baseline membership role grants.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-4">
                        {Object.entries(catalog).map(([role, permissions]) => (
                            <div key={role} className="rounded-lg border p-3">
                                <div className="text-xs font-semibold">
                                    {role}
                                </div>
                                <div className="mt-2 flex flex-wrap gap-1.5">
                                    {permissions.map((permission) => (
                                        <Badge
                                            key={permission}
                                            variant="secondary"
                                            className="font-mono"
                                        >
                                            {permission}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Role</TableHead>
                                    <TableHead>Organization</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Assigned
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {role.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {role.organization}
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-muted-foreground max-w-md truncate font-mono text-xs">
                                                {role.permissions.join(', ')}
                                            </div>
                                        </TableCell>
                                        <TableCell className="pr-6 text-right text-sm">
                                            {role.assignedTo}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {roles.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No roles yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
