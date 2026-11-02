import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

/**
 * In-app notification bell (PRD 5.7): unread badge, latest messages,
 * mark-read and mark-all-read — fed by the shared Inertia props.
 */
export default function NotificationBell() {
    const { notifications, unreadNotificationsCount } =
        usePage<PageProps>().props;

    const markAllRead = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label="Notifications"
                >
                    <Bell className="size-4" />
                    {unreadNotificationsCount > 0 && (
                        <span className="bg-destructive text-destructive-foreground absolute top-1 right-1 flex size-4 items-center justify-center rounded-full text-[9px] font-semibold">
                            {unreadNotificationsCount > 9
                                ? '9+'
                                : unreadNotificationsCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <div className="flex items-center justify-between px-2 py-1.5">
                    <span className="text-sm font-semibold">Notifications</span>
                    {unreadNotificationsCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-muted-foreground text-xs underline-offset-4 hover:underline"
                        >
                            Mark all read
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator />
                {notifications.length === 0 ? (
                    <div className="text-muted-foreground px-2 py-8 text-center text-sm">
                        Nothing here yet.
                    </div>
                ) : (
                    <div className="max-h-80 overflow-y-auto">
                        {notifications.map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                onSelect={() => {
                                    if (!notification.read) {
                                        router.post(
                                            `/notifications/${notification.id}/read`,
                                            {},
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                            },
                                        );
                                    }
                                    if (notification.url) {
                                        setTimeout(
                                            () =>
                                                router.visit(
                                                    notification.url as string,
                                                ),
                                            150,
                                        );
                                    }
                                }}
                                className="flex cursor-pointer flex-col items-start gap-0.5 py-2"
                            >
                                <span className="flex items-center gap-2 text-sm font-medium">
                                    {!notification.read && (
                                        <span className="bg-primary size-1.5 shrink-0 rounded-full" />
                                    )}
                                    {notification.title}
                                </span>
                                <span className="text-muted-foreground line-clamp-2 text-xs">
                                    {notification.body}
                                </span>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
