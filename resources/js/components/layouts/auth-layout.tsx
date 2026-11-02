import ApplicationLogo from '@/components/shared/application-logo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

/**
 * Centered card layout for authentication pages.
 */
export default function AuthLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-background relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <div className="absolute top-4 right-4">
                <ThemeToggle />
            </div>

            <Link href="/" className="mb-8 flex items-center gap-3">
                <ApplicationLogo className="size-10" />
                <span className="text-2xl font-semibold tracking-tight">
                    {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                </span>
            </Link>

            <div className="bg-card w-full max-w-md rounded-xl border p-6 shadow-sm sm:p-8">
                {children}
            </div>
        </div>
    );
}
