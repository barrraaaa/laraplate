import ApplicationLogo from '@/components/shared/application-logo';
import Seo from '@/components/shared/seo';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';

type Deal = {
    name: string;
    description: string;
    discount: string;
    code: string;
    url?: string;
};

/**
 * Sample partner-deal bundle (All-in perk). Replace with real partner
 * agreements before selling — values shown are placeholders that sum to
 * the marketed "worth of discounts".
 */
const DEALS: Deal[] = [
    {
        name: 'AI Pro subscription',
        description: 'AI-powered writing assistant for docs & copy.',
        discount: '$240/yr',
        code: 'LARAPLATE40',
    },
    {
        name: 'SaaS design kit',
        description: 'Figma UI kit with 200+ components.',
        discount: '$199',
        code: 'LARAPLATE50',
    },
    {
        name: 'Analytics suite',
        description: 'Plausible-style privacy analytics, 6 months free.',
        discount: '$210',
        code: 'LARAPLATE6M',
    },
    {
        name: 'Email platform',
        description: 'Transactional + marketing email credits.',
        discount: '$260',
        code: 'LARAPLATE200',
    },
    {
        name: 'SEO toolset',
        description: 'Rank tracking + site audits for a year.',
        discount: '$120/yr',
        code: 'LARAPLATEYR',
    },
    {
        name: 'Hosting credit',
        description: '$150 credit on the recommended PaaS.',
        discount: '$150',
        code: 'LARAPLATE150',
    },
];

export default function Deals() {
    return (
        <div className="bg-background flex min-h-screen flex-col">
            <Seo
                title="Partner deals"
                description="Exclusive partner discounts included with Laraplate All-in."
                canonical={`${window.location.origin}/deals`}
            />

            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
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

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-12">
                <Badge variant="outline" className="mb-4">
                    All-in perk
                </Badge>
                <h1 className="text-3xl font-semibold tracking-tight">
                    $1,210+ in partner deals
                </h1>
                <p className="text-muted-foreground mt-2 max-w-2xl">
                    Every All-in license includes this bundle. Codes are
                    distributed inside the private community after purchase.
                </p>

                <div className="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {DEALS.map((deal) => (
                        <Card key={deal.name}>
                            <CardContent className="p-5">
                                <div className="text-sm font-semibold">
                                    {deal.name}
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {deal.description}
                                </p>
                                <div className="mt-4 flex items-center justify-between">
                                    <Badge>{deal.discount} value</Badge>
                                    <code className="bg-muted rounded px-2 py-1 font-mono text-xs">
                                        {deal.code}
                                    </code>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <p className="text-muted-foreground mt-8 text-xs">
                    Sample page — replace the placeholder partners and values
                    (resources/js/pages/Deals.tsx) before publishing your
                    bundle.
                </p>
            </main>
        </div>
    );
}
