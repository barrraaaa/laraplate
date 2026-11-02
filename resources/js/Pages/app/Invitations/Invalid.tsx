import AuthLayout from '@/components/layouts/auth-layout';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { MailX } from 'lucide-react';

/**
 * Shown when an invitation link is expired, revoked or already used.
 */
export default function Invalid({ reason }: { reason?: string }) {
    const message =
        reason === 'expired'
            ? 'This invitation has expired. Ask an organization admin to send you a new one.'
            : 'This invitation link is no longer valid. It may have been revoked or already used.';

    return (
        <AuthLayout>
            <Head title="Invalid Invitation" />

            <div className="flex flex-col items-center text-center">
                <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                    <MailX className="text-muted-foreground size-6" />
                </div>

                <h1 className="mt-4 text-xl font-semibold tracking-tight">
                    Invitation not available
                </h1>
                <p className="text-muted-foreground mt-2 max-w-sm text-sm">
                    {message}
                </p>

                <Button className="mt-6" asChild>
                    <Link href={route('dashboard')}>Back to dashboard</Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
