import AdminLayout from '@/components/layouts/admin-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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

type OrganizationsProps = {
    organizations: {
        data: {
            id: number;
            name: string;
            slug: string | null;
            personal: boolean;
            membersCount: number;
            createdAt: string;
        }[];
        current_page: number;
        last_page: number;
    };
};

/**
 * Admin view over all organizations (blueprint: tenancy overview).
 */
export default function Organizations() {
    const { organizations } = usePage<PageProps<OrganizationsProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — Organizations" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Organizations
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Shared workspaces (personal anchors excluded from active
                        counts where relevant).
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Organization
                                    </TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-right">
                                        Members
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Created
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {organizations.data.map((organization) => (
                                    <TableRow key={organization.id}>
                                        <TableCell className="pl-6">
                                            <div className="text-sm font-medium">
                                                {organization.name}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {organization.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {organization.personal ? (
                                                <Badge variant="outline">
                                                    Personal
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Team
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {organization.membersCount}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                            {new Date(
                                                organization.createdAt,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {organizations.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No organizations yet.
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
