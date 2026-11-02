import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type TechLogo = {
    name: string;
    glyph: ReactNode;
};

const LOGOS: TechLogo[] = [
    {
        name: 'Laravel',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="currentColor"
                aria-hidden
            >
                <path
                    d="M4 5h3l2.5 8.5L12 5h3.5L21 19h-3.2l-1.3-3.6H7.5L6.2 19H3L4 5h0Z"
                    opacity="0.85"
                />
                <circle cx="6.7" cy="8.6" r="1.3" />
            </svg>
        ),
    },
    {
        name: 'React',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.4"
                aria-hidden
            >
                <ellipse cx="12" cy="12" rx="9.5" ry="3.6" />
                <ellipse
                    cx="12"
                    cy="12"
                    rx="9.5"
                    ry="3.6"
                    transform="rotate(60 12 12)"
                />
                <ellipse
                    cx="12"
                    cy="12"
                    rx="9.5"
                    ry="3.6"
                    transform="rotate(120 12 12)"
                />
                <circle
                    cx="12"
                    cy="12"
                    r="1.4"
                    fill="currentColor"
                    stroke="none"
                />
            </svg>
        ),
    },
    {
        name: 'TypeScript',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="currentColor"
                aria-hidden
            >
                <path d="M3 3h18v18H3V3Zm10.7 8.6h-2v6.1c0 1.2.7 1.9 1.9 1.4v1.9c-2.3.8-4.3-.3-4.3-3V11.6h-1.7V9.8h6.1v1.8Zm4.1 2h-1.9c0-1-.4-1.5-1.3-1.5-.7 0-1.1.4-1.1 1 0 .5.4.8 1.5 1.2 1.6.5 2.4 1.2 2.4 2.7 0 1.6-1.2 2.7-3.1 2.7-1.8 0-3-.8-3-2.9h2c0 1 .5 1.5 1.1 1.5.7 0 1-.4 1-1 0-.5-.4-.8-1.5-1.2-1.5-.5-2.4-1.2-2.4-2.6 0-1.5 1.1-2.6 2.9-2.6 1.6-.1 2.4.9 2.4 2.7Z" />
            </svg>
        ),
    },
    {
        name: 'Tailwind CSS',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                aria-hidden
            >
                <path d="M12 6c-2.5 0-4 1.2-4.5 3.5.9-1.2 2-1.6 3.3-1.3.7.2 1.2.7 1.8 1.3.8.9 1.8 1.9 4 1.9 2.5 0 4-1.2 4.5-3.5-.9 1.2-2 1.6-3.3 1.3-.7-.2-1.2-.7-1.8-1.3-.8-.9-1.8-1.9-4-1.9ZM7.5 12.9c-2.5 0-4 1.2-4.5 3.5.9-1.2 2-1.6 3.3-1.3.7.2 1.2.7 1.8 1.3.8.9 1.8 1.9 4 1.9 2.5 0 4-1.2 4.5-3.5-.9 1.2-2 1.6-3.3 1.3-.7-.2-1.2-.7-1.8-1.3-.8-.9-1.8-1.9-4-1.9Z" />
            </svg>
        ),
    },
    {
        name: 'Inertia.js',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="currentColor"
                aria-hidden
            >
                <path d="M12 3 20 20h-5l-3-6.5L9 20H4l8-17Z" />
            </svg>
        ),
    },
    {
        name: 'Stripe',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                aria-hidden
            >
                <path d="M3.5 7c2-1.5 4.5-2 7.5-1.5 2.3.4 4.3 1 6.5.5 1.2-.3 2.4-.8 3-1.5v11.5c-2 1.5-4.5 2-7.5 1.5-2.3-.4-4.3-1-6.5-.5-1.2.3-2.4.8-3 1.5V7Z" />
                <path d="M3.5 7v4.5M20.5 5v4.5" opacity="0.45" />
            </svg>
        ),
    },
    {
        name: 'Mailgun',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                aria-hidden
            >
                <rect x="3" y="5" width="18" height="14" rx="2.5" />
                <path d="m3.5 6.5 8.5 6 8.5-6" />
            </svg>
        ),
    },
    {
        name: 'Vite',
        glyph: (
            <svg
                viewBox="0 0 24 24"
                className="size-5"
                fill="currentColor"
                aria-hidden
            >
                <path d="M13.4 2.2 5 15.8h5.2l-.8 6 8.4-13.6h-5.2l.8-6Z" />
            </svg>
        ),
    },
];

/**
 * "Built on" logo wall for the sales hero — monochrome glyphs, no
 * external image assets, safe to replace with official press-kit logos.
 */
export default function TechLogoGrid({ className }: { className?: string }) {
    return (
        <div
            className={cn(
                'grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-2',
                className,
            )}
        >
            {LOGOS.map((logo) => (
                <div
                    key={logo.name}
                    className="bg-muted/50 flex items-center gap-2.5 rounded-xl border px-4 py-3"
                >
                    <span className="text-muted-foreground [&_svg]:shrink-0">
                        {logo.glyph}
                    </span>
                    <span className="text-sm font-medium">{logo.name}</span>
                </div>
            ))}
        </div>
    );
}
