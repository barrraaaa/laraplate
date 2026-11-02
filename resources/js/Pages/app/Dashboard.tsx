import AppLayout from '@/components/layouts/app-layout';
import DashboardPage from '@/features/dashboard/dashboard-page';
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AppLayout>
            <Head title="Dashboard" />
            <DashboardPage />
        </AppLayout>
    );
}
