import { cn } from '@/lib/utils';

/**
 * Avatar with graceful fallback to initials — used across the customer
 * app and the admin panel. Props intentionally tiny so every row, card
 * and menu can share the same control.
 */
export default function UserAvatar({
    name,
    url,
    className,
}: {
    name: string;
    url?: string | null;
    className?: string;
}) {
    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    if (url) {
        return (
            <img
                src={url}
                alt={name}
                className={cn(
                    'bg-muted size-8 shrink-0 rounded-full object-cover',
                    className,
                )}
            />
        );
    }

    return (
        <div
            className={cn(
                'bg-primary text-primary-foreground flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                className,
            )}
        >
            {initials || '?'}
        </div>
    );
}
