import AdminLayout from '@/components/layouts/admin-layout';
import UserAvatar from '@/components/shared/user-avatar';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Building2, CreditCard, DollarSign, Users } from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';

type DashboardProps = {
    metrics: {
        mrr: number;
        totalUsers: number;
        totalOrganizations: number;
        activeSubscriptions: number;
    };
    signups: { week: string; count: number }[];
    recentUsers: {
        id: number;
        name: string;
        email: string;
        createdAt: string;
    }[];
};

/**
 * Admin dashboard — mirrors the blueprint (MRR, tenancy, churn-surrogates,
 * signup trend). MRR aggregates paid invoices from the current month;
 * Stripe-reported values replace this with live webhook data.
 */
export default function Dashboard() {
    const { metrics, signups, recentUsers } =
        usePage<PageProps<DashboardProps>>().props;

    const tiles = [
        {
            label: 'Monthly revenue (MRR)',
            value: `$${Number(metrics.mrr / 100).toLocaleString('en-US', {
                minimumFractionDigits: 2,
            })}`,
            icon: DollarSign,
        },
        {
            label: 'Total users',
            value: String(metrics.totalUsers),
            icon: Users,
        },
        {
            label: 'Active organizations',
            value: String(metrics.totalOrganizations),
            icon: Building2,
        },
        {
            label: 'Active subscriptions',
            value: String(metrics.activeSubscriptions),
            icon: CreditCard,
        },
    ];

    return (
        <AdminLayout>
            <Head title="Admin — Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Platform overview — sample figures from local data.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {tiles.map((tile) => (
                        <Card key={tile.label}>
                            <CardContent className="flex items-center gap-4 p-5">
                                <div className="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-lg">
                                    <tile.icon className="size-5" />
                                </div>
                                <div>
                                    <div className="text-2xl font-semibold tracking-tight">
                                        {tile.value}
                                    </div>
                                    <div className="text-muted-foreground text-xs">
                                        {tile.label}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base">
                                New signups — last 8 weeks
                            </CardTitle>
                            <CardDescription>
                                Weekly registered users.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-56 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart
                                        data={signups}
                                        margin={{
                                            top: 8,
                                            right: 8,
                                            left: 0,
                                            bottom: 0,
                                        }}
                                    >
                                        <defs>
                                            <linearGradient
                                                id="signupsFill"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stopColor="var(--primary)"
                                                    stopOpacity={0.24}
                                                />
                                                <stop
                                                    offset="100%"
                                                    stopColor="var(--primary)"
                                                    stopOpacity={0.02}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid
                                            vertical={false}
                                            stroke="var(--border)"
                                        />
                                        <XAxis
                                            dataKey="week"
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{
                                                fill: 'var(--muted-foreground)',
                                                fontSize: 12,
                                            }}
                                        />
                                        <Tooltip
                                            cursor={{ stroke: 'var(--border)' }}
                                            contentStyle={{
                                                backgroundColor: 'var(--card)',
                                                border: '1px solid var(--border)',
                                                borderRadius: 8,
                                                color: 'var(--foreground)',
                                            }}
                                            formatter={(value) => [
                                                `${value} users`,
                                                'Signups',
                                            ]}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="count"
                                            stroke="var(--primary)"
                                            strokeWidth={2}
                                            fill="url(#signupsFill)"
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="text-muted-foreground mt-3 flex flex-wrap gap-x-6 gap-y-1 border-t pt-3 text-xs">
                                {signups.map((point) => (
                                    <span key={point.week}>
                                        {point.week}:{' '}
                                        <span className="text-foreground font-medium">
                                            {point.count}
                                        </span>
                                    </span>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Recent signups
                            </CardTitle>
                            <CardDescription>
                                Latest registered users.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            User
                                        </TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Joined
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentUsers.map((u) => (
                                        <TableRow key={u.id}>
                                            <TableCell className="pl-6">
                                                <div className="flex items-center gap-2.5">
                                                    <UserAvatar
                                                        name={u.name}
                                                        className="size-7"
                                                    />
                                                    <div>
                                                        <div className="text-sm font-medium">
                                                            {u.name}
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">
                                                            {u.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground pr-6 text-right text-xs">
                                                {new Date(
                                                    u.createdAt,
                                                ).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
