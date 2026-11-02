import ApplicationLogo from '@/components/shared/application-logo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
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
import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    BarChart3,
    Building2,
    CreditCard,
    HeartPulse,
    Inbox,
    Receipt,
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
        section: 'Platform',
        items: [
            { label: 'Webhooks', href: '/admin/webhooks', icon: Webhook },
            { label: 'Activity Logs', href: '/admin/activity', icon: Activity },
        ],
    },
];

/**
 * Platform admin shell — mirrors the admin panel blueprint
 * (laravel_saas_admin_panel_boilerplate.tsx): grouped sidebar navigation
 * and a top bar. Coupons/Webhooks pages land as dedicated screens later.
 */
export default function AdminLayout({ children }: PropsWithChildren) {
    const url = usePage().url;

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
                                            isActive={url === item.href}
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
            </Sidebar>

            <SidebarInset>
                <header className="flex h-14 items-center gap-3 border-b px-4">
                    <SidebarTrigger />
                    <Separator orientation="vertical" className="h-6" />
                    <div className="ml-auto flex items-center gap-2">
                        <ThemeToggle />
                    </div>
                </header>

                <div className="p-4 sm:p-6">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
