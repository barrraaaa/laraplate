import ApplicationLogo from '@/components/shared/application-logo';
import Seo from '@/components/shared/seo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ShieldCheck, Users, Zap } from 'lucide-react';

/**
 * Generic product landing — the starting point every buyer customizes
 * into their own product's marketing page. Login/register lead into the
 * app; Blog/Terms/Privacy are wired for legal + SEO basics.
 */
export default function Home() {
    const { canLogin, canRegister } =
        usePage<PageProps<{ canLogin: boolean; canRegister: boolean }>>().props;

    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Seo />

            <header className="bg-background/95 sticky top-0 z-40 border-b backdrop-blur">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-2.5">
                        <ApplicationLogo className="size-7" />
                        <span className="text-lg font-semibold tracking-tight">
                            {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                        </span>
                    </div>

                    <nav className="hidden items-center gap-1 md:flex">
                        <Link
                            href="/blog"
                            className="text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            Blog
                        </Link>
                    </nav>

                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        {canLogin && (
                            <Button variant="ghost" asChild>
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                        )}
                        {canRegister && (
                            <Button asChild>
                                <Link href={route('register')}>
                                    Get started
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
            </header>

            <main className="flex-1">
                <section className="mx-auto flex max-w-3xl flex-col items-center px-4 py-24 text-center">
                    <span className="text-muted-foreground rounded-full border px-3 py-1 text-xs font-medium">
                        Laravel · Inertia · React · Stripe
                    </span>
                    <h1 className="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl">
                        Ship your SaaS product faster
                    </h1>
                    <p className="text-muted-foreground mt-4 max-w-xl text-lg">
                        Production-oriented foundations — authentication,
                        tenancy, billing, entitlements and an admin panel —
                        ready to build on.
                    </p>
                    <div className="mt-8 flex items-center gap-3">
                        {canRegister && (
                            <Button size="lg" asChild>
                                <Link href={route('register')}>
                                    Start building
                                </Link>
                            </Button>
                        )}
                        {canLogin && (
                            <Button size="lg" variant="outline" asChild>
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                        )}
                    </div>
                </section>

                <section className="border-t py-20">
                    <div className="mx-auto max-w-6xl px-4">
                        <h2 className="text-center text-3xl font-semibold tracking-tight">
                            Everything a subscription product needs
                        </h2>
                        <p className="text-muted-foreground mx-auto mt-3 max-w-xl text-center">
                            One codebase, three modes — single-user, teams or
                            both. Replace the example module with your own
                            product.
                        </p>

                        <div className="mt-12 grid gap-4 md:grid-cols-3">
                            {[
                                {
                                    icon: ShieldCheck,
                                    title: 'Tenancy & roles',
                                    body: 'Organizations with invitations, ownership safeguards and organization-scoped permissions.',
                                },
                                {
                                    icon: Zap,
                                    title: 'Billing in a box',
                                    body: 'Stripe plans, checkout, idempotent webhooks and a local invoice mirror behind an adapter boundary.',
                                },
                                {
                                    icon: Users,
                                    title: 'Admin panel included',
                                    body: 'Metrics, customers, revenue and system health — the platform view your team needs.',
                                },
                            ].map((feature) => (
                                <Card key={feature.title}>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <feature.icon className="text-muted-foreground size-4" />
                                            {feature.title}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground text-sm">
                                            {feature.body}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>
            </main>

            <footer className="border-t py-6">
                <div className="text-muted-foreground mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 text-center text-sm sm:flex-row">
                    <span>
                        Powered by{' '}
                        {import.meta.env.VITE_APP_NAME || 'Laraplate'}
                    </span>
                    <div className="flex items-center gap-4">
                        <Link
                            href="/blog"
                            className="underline-offset-4 hover:underline"
                        >
                            Blog
                        </Link>
                        <Link
                            href="/terms"
                            className="underline-offset-4 hover:underline"
                        >
                            Terms
                        </Link>
                        <Link
                            href="/privacy"
                            className="underline-offset-4 hover:underline"
                        >
                            Privacy
                        </Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
