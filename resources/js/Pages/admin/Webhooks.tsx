import AdminLayout from '@/components/layouts/admin-layout';
import EmptyState from '@/components/shared/empty-state';
import { Head } from '@inertiajs/react';
import { Clock } from 'lucide-react';

/**
 * Outbound webhook deliveries land in V1.2 (PRD 5.8 P1) — placeholder so
 * the blueprint navigation stays complete.
 */
export default function Webhooks() {
    return (
        <AdminLayout>
            <Head title="Admin — Webhooks" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Webhooks
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Outbound endpoint delivery logs.
                    </p>
                </div>
                <EmptyState
                    icon={Clock}
                    title="Outbound webhooks arrive in V1.2"
                    description="Endpoint registration, signing, delivery logs and retries ship with the post-MVP roadmap (PRD 5.8 P1)."
                />
            </div>
        </AdminLayout>
    );
}
