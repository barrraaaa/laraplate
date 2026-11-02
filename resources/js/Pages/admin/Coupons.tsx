import AdminLayout from '@/components/layouts/admin-layout';
import EmptyState from '@/components/shared/empty-state';
import { Head } from '@inertiajs/react';
import { Clock } from 'lucide-react';

/**
 * Coupons land with the post-MVP roadmap (PRD 5.5 P1) — wire the sidebar
 * destination now so navigation never 404s.
 */
export default function Coupons() {
    return (
        <AdminLayout>
            <Head title="Admin — Coupons" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Coupons
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Discount codes and redemption rules.
                    </p>
                </div>
                <EmptyState
                    icon={Clock}
                    title="Coupon management arrives in V1.1"
                    description="The data model allows coupon codes, eligibility and expiry — this screen ships with the post-MVP roadmap while checkout stays focused on the plan catalog."
                />
            </div>
        </AdminLayout>
    );
}
