import ApplicationLogo from '@/components/shared/application-logo';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

/**
 * Placeholder privacy template — marked for buyer legal review
 * (PRD 5.14). Do not ship this text as-is.
 */
export default function Privacy() {
    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Head title="Privacy Policy" />

            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-3xl items-center justify-between px-4">
                    <Link href="/" className="flex items-center gap-2.5">
                        <ApplicationLogo className="size-6" />
                        <span className="font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                        </span>
                    </Link>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/">Back</Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-12">
                <p className="text-sm font-semibold tracking-wide text-amber-600 uppercase dark:text-amber-400">
                    Template — require legal review before publishing
                </p>
                <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                    Privacy Policy
                </h1>
                <p className="text-muted-foreground mt-2 text-sm">
                    Last updated: {new Date().toLocaleDateString()}
                </p>

                <div className="text-muted-foreground mt-8 space-y-5 text-sm leading-relaxed">
                    <p>
                        This application collects only the data it needs:
                        account details, your organization data and (through
                        Stripe) the payment information required for billing.
                        Payments are processed by Stripe and governed by its
                        privacy policy.
                    </p>
                    <p>
                        [Describe data categories, purposes, retention periods,
                        your region's data-subject rights, subprocessors and a
                        contact channel.]
                    </p>
                    <p>
                        This page is rendered from
                        resources/js/pages/legal/Privacy.tsx — replace the
                        sample text with your final privacy policy.
                    </p>
                </div>
            </main>
        </div>
    );
}
