import ApplicationLogo from '@/components/shared/application-logo';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

/**
 * Placeholder terms template — marked for buyer legal review (PRD 5.14).
 * Do not ship this text as-is.
 */
export default function Terms() {
    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Head title="Terms of Service" />

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
                    Terms of Service
                </h1>
                <p className="text-muted-foreground mt-2 text-sm">
                    Last updated: {new Date().toLocaleDateString()}
                </p>

                <div className="text-muted-foreground mt-8 space-y-5 text-sm leading-relaxed">
                    <p>
                        [Your company] provides the
                        {import.meta.env.VITE_APP_NAME || 'your product'}{' '}
                        service subject to these terms. By creating an account
                        you agree to them.
                    </p>
                    <p>
                        [Describe permitted use, user responsibilities,
                        subscription and payment terms, cancellation and refund
                        policy, limitation of liability, governing law and
                        contact details.]
                    </p>
                    <p>
                        This page is rendered from
                        resources/js/pages/legal/Terms.tsx — replace the sample
                        text with your final legal terms.
                    </p>
                </div>
            </main>
        </div>
    );
}
