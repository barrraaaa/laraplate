import ApplicationLogo from '@/components/shared/application-logo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

/**
 * Landing page. Ships as a minimal, editable example — the full marketing
 * page set (hero, pricing, terms/privacy) is part of the productization
 * phase.
 */
export default function Home() {
    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Head title="Welcome" />

            <header className="bg-background/95 sticky top-0 z-40 border-b backdrop-blur">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-2.5">
                        <ApplicationLogo className="size-7" />
                        <span className="text-lg font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                        </span>
                    </div>

                    <div className="flex items-center gap-3">
                        <ThemeToggle />
                        <Button variant="ghost" asChild>
                            <Link href={route('login')}>Log in</Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('register')}>Get started</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-4 py-24 text-center">
                <span className="text-muted-foreground rounded-full border px-3 py-1 text-xs font-medium">
                    Laravel · Inertia · React
                </span>
                <h1 className="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Ship your SaaS product faster
                </h1>
                <p className="text-muted-foreground mt-4 max-w-xl text-lg">
                    {import.meta.env.VITE_APP_NAME || 'Laraplate'} is a
                    production-oriented foundation: authentication, tenancy,
                    billing, entitlements and an admin panel — ready to build
                    on.
                </p>
                <div className="mt-8 flex items-center gap-3">
                    <Button size="lg" asChild>
                        <Link href={route('register')}>Start building</Link>
                    </Button>
                    <Button size="lg" variant="outline" asChild>
                        <Link href={route('login')}>Log in</Link>
                    </Button>
                </div>
            </main>

            <footer className="text-muted-foreground border-t py-6 text-center text-sm">
                Powered by {import.meta.env.VITE_APP_NAME || 'Laraplate'} — edit
                this page in resources/js/pages/Home.tsx
            </footer>
        </div>
    );
}
