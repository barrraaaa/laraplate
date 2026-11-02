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
import { Link } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Building2,
    CreditCard,
    HeartPulse,
    Inbox,
    Receipt,
    Settings,
    Tag,
    Users,
    Webhook,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';

/**
 * Platform admin shell — admin pages (Phase 4) render inside this layout.
 * Navigation mirrors the admin panel blueprint (laravel_saas_admin_panel
 * .tsx); hrefs are wired to real routes as each admin screen ships.
 */
export default function AdminLayout({ children }: PropsWithChildren) {
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
                    <AdminSidebarGroup label="Overview">
                        <AdminSidebarItem icon={BarChart3} label="Dashboard" />
                        <AdminSidebarItem
                            icon={HeartPulse}
                            label="System Health"
                        />
                    </AdminSidebarGroup>

                    <AdminSidebarGroup label="Customers">
                        <AdminSidebarItem icon={Users} label="Users" />
                        <AdminSidebarItem
                            icon={Building2}
                            label="Organizations"
                        />
                        <AdminSidebarItem
                            icon={CreditCard}
                            label="Subscriptions"
                        />
                    </AdminSidebarGroup>

                    <AdminSidebarGroup label="Commerce">
                        <AdminSidebarItem icon={Receipt} label="Transactions" />
                        <AdminSidebarItem icon={Inbox} label="Invoices" />
                        <AdminSidebarItem icon={Tag} label="Coupons" />
                    </AdminSidebarGroup>

                    <AdminSidebarGroup label="Platform">
                        <AdminSidebarItem icon={Webhook} label="Webhooks" />
                        <AdminSidebarItem
                            icon={Activity}
                            label="Activity Logs"
                        />
                        <AdminSidebarItem icon={Settings} label="Settings" />
                    </AdminSidebarGroup>
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

function AdminSidebarGroup({
    label,
    children,
}: PropsWithChildren<{ label: string }>) {
    return (
        <SidebarGroup>
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>{children}</SidebarMenu>
        </SidebarGroup>
    );
}

function AdminSidebarItem({
    icon: Icon,
    label,
}: {
    icon: typeof Users;
    label: string;
}) {
    return (
        <SidebarMenuItem>
            {/* Admin routes land in Phase 4 — hrefs are stubbed meanwhile. */}
            <SidebarMenuButton asChild>
                <Link href="#">
                    <Icon />
                    <span>{label}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}
