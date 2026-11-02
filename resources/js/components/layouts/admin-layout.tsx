import ApplicationLogo from '@/components/shared/application-logo';
import ThemeToggle from '@/components/shared/theme-toggle';
import UserAvatar from '@/components/shared/user-avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { useAuth } from '@/hooks/use-organization';
import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    BarChart3,
    Bell,
    Building2,
    CreditCard,
    FileText,
    HeartPulse,
    Inbox,
    KeyRound,
    LogOut,
    Receipt,
    Settings2,
    ShieldCheck,
    Tag,
    Users,
    Webhook,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';

const NAVIGATION: {
    section: string;
    items: { label: string; href: string; icon: typeof Users }[];
}[] = [
    {
        section: 'Overview',
        items: [
            { label: 'Dashboard', href: '/admin', icon: BarChart3 },
            { label: 'System Health', href: '/admin/health', icon: HeartPulse },
            {
                label: 'System Settings',
                href: '/admin/settings',
                icon: Settings2,
            },
        ],
    },
    {
        section: 'Customers',
        items: [
            { label: 'Users', href: '/admin/users', icon: Users },
            {
                label: 'Organizations',
                href: '/admin/organizations',
                icon: Building2,
            },
            {
                label: 'Subscriptions',
                href: '/admin/subscriptions',
                icon: CreditCard,
            },
        ],
    },
    {
        section: 'Commerce',
        items: [
            {
                label: 'Transactions',
                href: '/admin/transactions',
                icon: Receipt,
            },
            { label: 'Invoices', href: '/admin/invoices', icon: Inbox },
            { label: 'Coupons', href: '/admin/coupons', icon: Tag },
        ],
    },
    {
        section: 'Content',
        // The blog ships as the admin-side sample CRUD — form pages,
        // validation and destructive confirmation live right here.
        items: [{ label: 'Blog', href: '/admin/blog', icon: FileText }],
    },
    {
        section: 'Platform',
        items: [
            { label: 'Security', href: '/admin/security', icon: ShieldCheck },
            { label: 'API Tokens', href: '/admin/api-tokens', icon: KeyRound },
            {
                label: 'Notifications',
                href: '/admin/notifications',
                icon: Bell,
            },
            { label: 'Webhooks', href: '/admin/webhooks', icon: Webhook },
            { label: 'Activity Logs', href: '/admin/activity', icon: Activity },
        ],
    },
];

/**
 * Platform admin shell — mirror of the admin panel blueprint. Active
 * navigation follows the current URL (including sub-pages), the sidebar
 * footer carries the signed-in admin, and the top bar mirrors the app
 * shell's user menu.
 */
export default function AdminLayout({ children }: PropsWithChildren) {
    const url = usePage().url;

    const isActive = (href: string): boolean =>
        href === '/admin'
            ? url === '/admin'
            : url === href || url.startsWith(href + '/');

    return (
        <SidebarProvider>
            <Sidebar>
                <SidebarHeader>
                    <Link
                        href={route('dashboard')}
                        className="flex items-center gap-2.5 px-2"
                    >
                        <ApplicationLogo className="size-7" />
                        <span className="text-base font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                            <span className="bg-muted text-muted-foreground ml-1.5 rounded px-1.5 py-0.5 text-[10px] font-medium tracking-wide uppercase">
                                Admin
                            </span>
                        </span>
                    </Link>
                </SidebarHeader>

                <SidebarContent>
                    {NAVIGATION.map((group) => (
                        <SidebarGroup key={group.section}>
                            <SidebarGroupLabel>
                                {group.section}
                            </SidebarGroupLabel>
                            <SidebarMenu>
                                {group.items.map((item) => (
                                    <SidebarMenuItem key={item.href}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={isActive(item.href)}
                                        >
                                            <Link href={item.href}>
                                                <item.icon />
                                                <span>{item.label}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    ))}

                    <SidebarGroup>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild>
                                    <Link href={route('dashboard')}>
                                        <ArrowLeft />
                                        <span>Back to app</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroup>
                </SidebarContent>

                <SidebarFooter>
                    <div className="flex items-center gap-2.5 px-2 py-2">
                        <UserAvatar
                            name={useAuth().user.name}
                            url={useAuth().user.avatar_url}
                            className="size-8"
                        />
                        <div className="min-w-0 flex-1">
                            <div className="truncate text-sm font-medium">
                                {useAuth().user.name}
                            </div>
                            <div className="text-muted-foreground truncate text-xs">
                                {useAuth().user.email}
                            </div>
                        </div>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            aria-label="Log out"
                            className="text-muted-foreground hover:text-foreground rounded-md p-1.5 transition-colors"
                        >
                            <LogOut className="size-4" />
                        </Link>
                    </div>
                </SidebarFooter>
            </Sidebar>

            <SidebarInset>
                <header className="flex h-14 items-center gap-3 border-b px-4">
                    <SidebarTrigger />
                    <Separator orientation="vertical" className="h-6" />
                    <div className="ml-auto flex items-center gap-2">
                        <ThemeToggle />
                        <AdminUserMenu />
                    </div>
                </header>

                <div className="p-4 sm:p-6">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}

function AdminUserMenu() {
    const { user } = useAuth();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    aria-label="Account menu"
                    className="bg-primary text-primary-foreground flex size-8 items-center justify-center overflow-hidden rounded-full text-xs font-semibold"
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
                <DropdownMenuItem asChild>
                    <Link href={route('dashboard')}>Back to app</Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
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
