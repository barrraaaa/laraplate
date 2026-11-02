import { cn } from '@/lib/utils';

export default function FormError({
    message,
    className,
}: {
    message?: string;
    className?: string;
}) {
    if (!message) {
        return null;
    }

    return (
        <p
            className={cn(
                'text-destructive mt-1.5 text-sm font-medium',
                className,
            )}
        >
            {message}
        </p>
    );
}
