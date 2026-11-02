import ApplicationLogo from '@/components/shared/application-logo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTrigger,
} from '@/components/ui/sheet';
import NotificationBell from '@/features/notifications/notification-bell';
import OrganizationSwitcher from '@/features/teams/organization-switcher';
import { useAuth, useCurrentOrganization } from '@/hooks/use-organization';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Menu } from 'lucide-react';
import type { PropsWithChildren, ReactNode } from 'react';

/**
 * Customer-facing application shell: top navigation, account menu and
 * theme toggle — shared by every authenticated page.
 */
export default function AppLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const organization = useCurrentOrganization();
    const { availableOrganizations } = usePage<PageProps>().props;
    const url = usePage().url;

    const navigation = [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            current: url === route('dashboard'),
        },
        {
            label: 'Billing',
            href: route('billing.show'),
            current: url.startsWith('/billing'),
        },
        {
            label: 'Settings',
            href: route('profile.edit'),
            current: url.startsWith('/profile'),
        },
    ];

    return (
        <div className="bg-muted/40 min-h-screen">
            <header className="bg-background/95 supports-[backdrop-filter]:bg-background/60 sticky top-0 z-40 border-b backdrop-blur">
                <div className="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-6">
                        <Link
                            href={route('dashboard')}
                            className="flex items-center gap-2.5"
                        >
                            <ApplicationLogo className="size-7" />
                            <span className="hidden text-lg font-semibold tracking-tight sm:block">
                                {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-1 md:flex">
                            {navigation.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={
                                        item.current
                                            ? 'bg-secondary rounded-md px-3 py-1.5 text-sm font-medium'
                                            : 'text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium transition-colors'
                                    }
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    <div className="flex items-center gap-2">
                        {availableOrganizations.length > 0 ? (
                            <OrganizationSwitcher
                                organizations={availableOrganizations}
                            />
                        ) : (
                            organization &&
                            !organization.personal && (
                                <span className="text-muted-foreground hidden items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium sm:inline-flex">
                                    <BadgeCheck className="size-3.5" />
                                    {organization.name}
                                </span>
                            )
                        )}

                        <ThemeToggle />

                        <NotificationBell />

                        <UserMenu />

                        <div className="md:hidden">
                            <Sheet>
                                <SheetTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Open menu"
                                    >
                                        <Menu className="size-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="right" className="w-64">
                                    <SheetHeader>
                                        <span className="text-lg font-semibold">
                                            {import.meta.env.VITE_APP_NAME ||
                                                'Laraplate'}
                                        </span>
                                    </SheetHeader>
                                    <nav className="mt-6 flex flex-col gap-1">
                                        {navigation.map((item) => (
                                            <Link
                                                key={item.label}
                                                href={item.href}
                                                className="hover:bg-secondary rounded-md px-3 py-2 text-sm font-medium"
                                            >
                                                {item.label}
                                            </Link>
                                        ))}
                                    </nav>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </div>
            </header>

            {header}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}

function UserMenu() {
    const { user } = useAuth();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    className="bg-primary text-primary-foreground flex size-8 items-center justify-center overflow-hidden rounded-full text-xs font-semibold"
                    aria-label="Account menu"
                >
                    {user.avatar_url ? (
                        <img
                            src={user.avatar_url}
                            alt={user.name}
                            className="size-full object-cover"
                        />
                    ) : (
                        user.name.charAt(0).toUpperCase()
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>
                    <div className="text-sm font-medium">{user.name}</div>
                    <div className="text-muted-foreground truncate text-xs font-normal">
                        {user.email}
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={route('profile.edit')}>Profile settings</Link>
                </DropdownMenuItem>
                {user.is_platform_admin && (
                    <DropdownMenuItem asChild>
                        <Link href="/admin">Admin panel</Link>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem asChild>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="w-full"
                    >
                        Log out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
