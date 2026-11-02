import AppLayout from '@/components/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import CreateOrganizationDialog from '@/features/teams/create-organization-dialog';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, ChevronRight } from 'lucide-react';

type OrganizationRow = {
    id: number;
    name: string;
    slug: string | null;
};

type IndexProps = {
    organizations: OrganizationRow[];
    activeOrganizationId: number | null;
    personalOrganization: { id: number; name: string } | null;
};

/**
 * Shared organizations overview (team & hybrid modes — PRD 5.3).
 */
export default function Index() {
    const { organizations, activeOrganizationId, personalOrganization } =
        usePage<PageProps<IndexProps>>().props;

    return (
        <AppLayout>
            <Head title="Organizations" />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Organizations
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Shared workspaces you own or belong to.
                    </p>
                </div>

                <CreateOrganizationDialog />
            </div>

            <div className="mt-6 space-y-3">
                {personalOrganization && (
                    <Card className="border-dashed">
                        <CardContent className="flex items-center gap-4 p-4">
                            <Building2 className="text-muted-foreground size-5" />
                            <div className="flex-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium">
                                        {personalOrganization.name}
                                    </span>
                                    <Badge variant="secondary">Personal</Badge>
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    Your private workspace — always with you.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {organizations.map((organization) => (
                    <Card key={organization.id}>
                        <CardContent className="p-0">
                            <Link
                                href={`/organizations/${organization.id}`}
                                className="hover:bg-muted/50 flex items-center gap-4 p-4 transition-colors"
                            >
                                <Building2 className="text-muted-foreground size-5" />
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="truncate text-sm font-medium">
                                            {organization.name}
                                        </span>
                                        {activeOrganizationId ===
                                            organization.id && (
                                            <Badge>Active</Badge>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {organization.slug}
                                    </p>
                                </div>
                                <ChevronRight className="text-muted-foreground size-4" />
                            </Link>
                        </CardContent>
                    </Card>
                ))}

                {organizations.length === 0 && (
                    <p className="text-muted-foreground rounded-xl border border-dashed px-4 py-12 text-center text-sm">
                        No organizations yet — create one to start
                        collaborating.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
