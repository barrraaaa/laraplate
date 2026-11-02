import ApplicationLogo from '@/components/shared/application-logo';
import Seo from '@/components/shared/seo';
import ThemeToggle from '@/components/shared/theme-toggle';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgePercent,
    Bot,
    Check,
    CreditCard,
    Disc3,
    FileText,
    Gauge,
    Infinity as InfinityIcon,
    Rocket,
    Search,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import { motion } from 'motion/react';

type HomeProps = {
    canLogin: boolean;
    canRegister: boolean;
    store: { starter: string | null; allIn: string | null };
    communityUrl: string | null;
};

const FEATURES = [
    {
        icon: Rocket,
        title: 'Laravel SaaS boilerplate',
        body: 'Tenancy, authentication, organizations, roles — the whole foundation.',
    },
    {
        icon: Search,
        title: 'SEO & blog',
        body: 'Public blog with per-post meta, plus a dependency-free sitemap.',
    },
    {
        icon: CreditCard,
        title: 'Stripe billing',
        body: 'Plans, checkout, idempotent webhooks, invoices and entitlements.',
    },
    {
        icon: Sparkles,
        title: 'Google OAuth & magic links',
        body: 'One-click sign in — passwordless, email-proven.',
    },
    {
        icon: Bot,
        title: 'Components & animations',
        body: 'shadcn/ui design system + motion-powered landing.',
    },
    {
        icon: FileText,
        title: 'Mailgun emails',
        body: 'Queued transactional email with a drop-in Mailgun driver.',
    },
    {
        icon: Disc3,
        title: 'SQLite & MySQL',
        body: 'Zero-config local SQLite; MySQL/Postgres for production.',
    },
    {
        icon: ShieldCheck,
        title: 'ChatGPT prompts',
        body: 'Ready prompts for terms, privacy, PRD and sales copy.',
    },
    {
        icon: Users,
        title: 'Discord community',
        body: 'Private community, changelog feed and leaderboard access.',
    },
    {
        icon: BadgePercent,
        title: '$1,210 in discounts',
        body: 'Partner deal bundle included with every All-in license.',
    },
    {
        icon: InfinityIcon,
        title: 'Lifetime updates',
        body: 'Pay once — every V1.x update included.',
    },
    {
        icon: Gauge,
        title: 'Build unlimited projects',
        body: 'No per-project fees. Your products, forever.',
    },
];

const FAQ = [
    {
        q: 'Is it a Laravel app or a library?',
        a: 'A full Laravel app you own the code of — fork it, modify it, ship products with it. Updates are merged manually; the docs explain how to keep custom modules mergeable.',
    },
    {
        q: 'Single-user or teams?',
        a: 'Both: flip SAAS_MODE between single, team and hybrid. Billing, tenancy and entitlements work in every mode.',
    },
    {
        q: 'Can I use it for client projects?',
        a: 'Yes — All-in includes client-work rights. Starter covers your own products.',
    },
    {
        q: 'Do I need a Stripe account today?',
        a: 'No. The app runs fully on the free plan without keys; drop your keys + price ids in when you are ready for checkout.',
    },
];

/**
 * Sales landing (ShipFast-style): sells the boilerplate. The live app
 * lives behind "Live demo" in the nav.
 */
export default function Home() {
    const { store, communityUrl, canLogin } =
        usePage<PageProps<HomeProps>>().props;

    const buyStarter = store.starter ?? '/#pricing';
    const buyAllIn = store.allIn ?? '/#pricing';

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
                            href="/#features"
                            className="text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            Features
                        </Link>
                        <Link
                            href="/#pricing"
                            className="text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            Pricing
                        </Link>
                        <Link
                            href="/blog"
                            className="text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            Blog
                        </Link>
                        <Link
                            href="/deals"
                            className="text-muted-foreground hover:text-foreground rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            Deals
                        </Link>
                    </nav>
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        {canLogin && (
                            <Button variant="ghost" asChild>
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href={route('register')}>Live demo</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                <section className="mx-auto max-w-4xl px-4 py-20 text-center sm:py-28">
                    <motion.div
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                    >
                        <Badge variant="outline" className="mb-6">
                            <BadgePercent className="mr-1.5 size-3.5" />
                            $1,210+ in partner discounts included
                        </Badge>
                        <h1 className="text-4xl font-semibold tracking-tight sm:text-6xl">
                            Ship your SaaS in a weekend
                        </h1>
                        <p className="text-muted-foreground mx-auto mt-5 max-w-2xl text-lg">
                            The Laravel boilerplate that ships with tenancy,
                            Stripe billing, an admin panel, SEO & blog,
                            passwordless auth — everything boring, already
                            built.
                        </p>
                        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <Button size="lg" asChild>
                                <a href={buyStarter}>
                                    Get{' '}
                                    {import.meta.env.VITE_APP_NAME ||
                                        'Laraplate'}{' '}
                                    — $199
                                    <ArrowRight className="ml-1.5 size-4" />
                                </a>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <Link href={route('register')}>
                                    Try the live demo
                                </Link>
                            </Button>
                        </div>
                        <p className="text-muted-foreground mt-4 text-xs">
                            Pay once · lifetime updates · build unlimited
                            projects
                        </p>
                    </motion.div>
                </section>

                <section id="features" className="border-t py-20">
                    <div className="mx-auto max-w-6xl px-4">
                        <h2 className="text-center text-3xl font-semibold tracking-tight">
                            Everything already built
                        </h2>
                        <p className="text-muted-foreground mx-auto mt-3 max-w-xl text-center">
                            No assembly required — fork, add your product idea,
                            deploy.
                        </p>

                        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {FEATURES.map((feature, index) => (
                                <motion.div
                                    key={feature.title}
                                    initial={{ opacity: 0, y: 10 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true }}
                                    transition={{
                                        duration: 0.35,
                                        delay: (index % 3) * 0.06,
                                    }}
                                >
                                    <Card className="h-full">
                                        <CardContent className="p-5">
                                            <feature.icon className="text-primary size-5" />
                                            <h3 className="mt-3 text-sm font-semibold">
                                                {feature.title}
                                            </h3>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {feature.body}
                                            </p>
                                        </CardContent>
                                    </Card>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                <section id="pricing" className="border-t py-20">
                    <div className="mx-auto max-w-4xl px-4">
                        <h2 className="text-center text-3xl font-semibold tracking-tight">
                            Pay once. Build unlimited projects.
                        </h2>
                        <p className="text-muted-foreground mx-auto mt-3 max-w-lg text-center">
                            Launch pricing — prices go up as the roadmap lands.
                        </p>

                        <div className="mt-12 grid gap-6 md:grid-cols-2">
                            {[
                                {
                                    name: 'Starter',
                                    listPrice: '$299',
                                    price: '$199',
                                    cta: 'Get Starter',
                                    href: buyStarter,
                                    items: [
                                        'The full codebase',
                                        'Lifetime V1.x updates',
                                        'Unlimited products for you',
                                        'Discord community access',
                                        'All partner discount codes',
                                    ],
                                },
                                {
                                    name: 'All-in',
                                    listPrice: '$349',
                                    price: '$249',
                                    cta: 'Get All-in',
                                    href: buyAllIn,
                                    featured: true,
                                    items: [
                                        'Everything in Starter',
                                        'Client-work license',
                                        'Every future V1.x module first',
                                        'Email support',
                                        'Priority community channel',
                                        'Deals bundle activation',
                                    ],
                                },
                            ].map((tier) => (
                                <Card
                                    key={tier.name}
                                    className={
                                        tier.featured
                                            ? 'border-primary/50 ring-primary/20 ring-1'
                                            : ''
                                    }
                                >
                                    <CardContent className="p-8">
                                        <div className="flex items-center justify-between">
                                            <h3 className="text-lg font-semibold">
                                                {tier.name}
                                            </h3>
                                            {tier.featured && (
                                                <Badge>Most popular</Badge>
                                            )}
                                        </div>
                                        <div className="mt-4 flex items-baseline gap-2">
                                            <span className="text-4xl font-semibold tracking-tight">
                                                {tier.price}
                                            </span>
                                            <span className="text-muted-foreground text-lg line-through">
                                                {tier.listPrice}
                                            </span>
                                            <span className="text-muted-foreground text-sm">
                                                USD
                                            </span>
                                        </div>

                                        <ul className="mt-6 space-y-2.5 text-sm">
                                            {tier.items.map((item) => (
                                                <li
                                                    key={item}
                                                    className="flex items-start gap-2"
                                                >
                                                    <Check className="text-primary mt-0.5 size-4 shrink-0" />
                                                    {item}
                                                </li>
                                            ))}
                                        </ul>

                                        <Button
                                            className="mt-8 w-full"
                                            size="lg"
                                            asChild
                                        >
                                            <a href={tier.href}>{tier.cta}</a>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <p className="text-muted-foreground mt-6 text-center text-xs">
                            Prices point at your checkout URLs — set
                            STORE_STARTER_URL / STORE_ALL_IN_URL in .env
                            (Gumroad, Lemon Squeezy, Stripe Payment Links…).
                        </p>
                    </div>
                </section>

                <section className="border-t py-20">
                    <div className="mx-auto max-w-2xl px-4">
                        <h2 className="text-center text-3xl font-semibold tracking-tight">
                            FAQ
                        </h2>

                        <div className="mt-10 space-y-4">
                            {FAQ.map((item) => (
                                <div
                                    key={item.q}
                                    className="rounded-xl border p-5"
                                >
                                    <h3 className="text-sm font-semibold">
                                        {item.q}
                                    </h3>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {item.a}
                                    </p>
                                </div>
                            ))}
                        </div>

                        {communityUrl && (
                            <div className="mt-10 flex justify-center">
                                <Button variant="outline" asChild>
                                    <a
                                        href={communityUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Join the community
                                    </a>
                                </Button>
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <footer className="border-t py-8">
                <div className="text-muted-foreground mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 text-center text-sm sm:flex-row">
                    <span>
                        © {new Date().getFullYear()}{' '}
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
                            href="/deals"
                            className="underline-offset-4 hover:underline"
                        >
                            Deals
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
