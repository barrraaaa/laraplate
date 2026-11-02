import { cn } from '@/lib/utils';

/**
 * Laraplate brand mark. Kept as inline SVG so no binary assets are
 * required — buyers replace this with their own logo.
 */
export default function ApplicationLogo({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 40 40"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={cn('size-8', className)}
        >
            <rect width="40" height="40" rx="10" className="fill-primary" />
            <path
                d="M13 12h14M13 20h10M13 28h6"
                className="stroke-primary-foreground"
                strokeWidth="3"
                strokeLinecap="round"
            />
        </svg>
    );
}
