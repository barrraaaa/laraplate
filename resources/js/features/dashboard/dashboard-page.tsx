import EmptyState from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAuth, useCurrentOrganization } from '@/hooks/use-organization';
import { Link } from '@inertiajs/react';
import { CreditCard, LibraryBig, Sparkles, UserRound } from 'lucide-react';

/**
 * Customer dashboard (single-user mode): the buyer replaces this feature
 * slice with their own product's home screen.
 */
export default function DashboardPage() {
    const { user } = useAuth();
    const organization = useCurrentOrganization();

    const firstName = user.name.split(' ')[0] ?? user.name;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Welcome back, {firstName}
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    {organization && !organization.personal
                        ? `You are working in ${organization.name}.`
                        : 'This is your personal workspace.'}
                </p>
            </div>

            {/* Stub cards — replaced by real product screens as you build on
                the boilerplate. Delete freely; it demonstrates layout patterns. */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserRound className="text-muted-foreground size-4" />
                            Profile
                        </CardTitle>
                        <CardDescription>
                            Manage your name, email and password.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('profile.edit')}>
                                Open settings
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CreditCard className="text-muted-foreground size-4" />
                            Billing
                        </CardTitle>
                        <CardDescription>
                            Subscription management arrives with the billing
                            module (Phase 3).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button variant="outline" size="sm" disabled>
                            Coming soon
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <LibraryBig className="text-muted-foreground size-4" />
                            Example module
                        </CardTitle>
                        <CardDescription>
                            The Projects module demonstrates the extension
                            pattern end-to-end.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button variant="outline" size="sm" disabled>
                            Coming soon
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <EmptyState
                icon={Sparkles}
                title="Your product starts here"
                description="Laraplate ships the foundation — auth, tenancy, billing, admin — so you can replace this dashboard with the app you're actually building."
                action={
                    <Button asChild variant="secondary">
                        <Link
                            href="https://github.com/larasoft"
                            target="_blank"
                        >
                            Read the docs
                        </Link>
                    </Button>
                }
            />
        </div>
    );
}
