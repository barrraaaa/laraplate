import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrentOrganization } from '@/hooks/use-organization';
import { router } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Settings2 } from 'lucide-react';

export type OrganizationListItem = {
    id: number;
    name: string;
    slug: string | null;
    personal?: boolean;
};

/**
 * Active-organization switcher in the app shell (PRD 5.3). Lists every
 * organization the user belongs to and switches the session context.
 */
export default function OrganizationSwitcher({
    organizations,
}: {
    organizations: OrganizationListItem[];
}) {
    const current = useCurrentOrganization();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="max-w-52 gap-2">
                    <Building2 className="text-muted-foreground size-4 shrink-0" />
                    <span className="truncate">
                        {current && !current.personal
                            ? current.name
                            : 'Workspace'}
                    </span>
                    <ChevronsUpDown className="text-muted-foreground size-3.5 shrink-0" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-64">
                <DropdownMenuLabel>Switch organization</DropdownMenuLabel>
                <DropdownMenuGroup>
                    {organizations.map((organization) => (
                        <DropdownMenuItem
                            key={organization.id}
                            onSelect={() =>
                                router.post(
                                    `/organizations/${organization.id}/switch`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <span className="flex-1 truncate">
                                {organization.name}
                            </span>
                            {current?.id === organization.id && (
                                <Check className="text-primary size-4" />
                            )}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <a href="/organizations" className="cursor-pointer">
                        <Settings2 className="mr-2 size-4" />
                        Manage organizations
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
