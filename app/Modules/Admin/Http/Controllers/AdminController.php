<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

/**
 * Platform admin panel (PRD 5.11) — operational views over users,
 * organizations, revenue and system health. Gated by EnsurePlatformAdmin.
 */
class AdminController extends Controller
{
    use AuthorizesRequests;

    public function dashboard(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'metrics' => [
                'mrr' => Invoice::query()
                    ->where('status', 'paid')
                    ->where('period_end', '>=', now()->startOfMonth())
                    ->sum('amount_paid'),
                'totalUsers' => User::query()->count(),
                'totalOrganizations' => Organization::query()->where('personal', false)->count(),
                'activeSubscriptions' => Subscription::query()
                    ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                    ->count(),
            ],
            'signups' => $this->weeklySignups(),
            'recentUsers' => User::query()
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'createdAt' => $user->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Weekly signup series for the dashboard trend (8 weeks).
     *
     * @return array<int, array{week: string, count: int}>
     */
    private function weeklySignups(): array
    {
        $start = now()->subWeeks(7)->startOfWeek();
        $weeks = [];

        foreach (range(0, 7) as $offset) {
            $weekStart = $start->copy()->addWeeks($offset);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weeks[] = [
                'week' => $weekStart->format('M j'),
                'count' => (int) User::query()
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->count(),
            ];
        }

        return $weeks;
    }

    public function users(Request $request): Response
    {
        $query = User::query()
            ->withCount('organizations')
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return Inertia::render('admin/Users', [
            'users' => $query->paginate(20)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'verified' => $user->email_verified_at !== null,
                    'organizationsCount' => $user->organizations_count,
                    'createdAt' => $user->created_at?->toIso8601String(),
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function organizations(Request $request): Response
    {
        $query = Organization::query()
            ->withCount('users')
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        return Inertia::render('admin/Organizations', [
            'organizations' => $query->paginate(20)
                ->withQueryString()
                ->through(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'personal' => $organization->isPersonal(),
                    'membersCount' => $organization->users_count,
                    'createdAt' => $organization->created_at?->toIso8601String(),
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function subscriptions(): Response
    {
        $page = Subscription::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        // The subscribe model has no owner relation in this Cashier
        // version — resolve organization names from the polymorphic id.
        $organizationNames = Organization::query()
            ->whereKey($page->getCollection()
                ->map(fn (Subscription $subscription) => $subscription->getAttribute('billable_id'))
                ->filter()
                ->unique())
            ->pluck('name', 'id');

        return Inertia::render('admin/Subscriptions', [
            'subscriptions' => $page
                ->through(fn (Subscription $subscription) => [
                    'id' => $subscription->id,
                    'status' => $subscription->stripe_status,
                    'price' => $subscription->stripe_price,
                    'owner' => $organizationNames[$subscription->getAttribute('billable_id')] ?? '—',
                    'endsAt' => $subscription->ends_at?->toIso8601String(),
                    'createdAt' => $subscription->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function transactions(): Response
    {
        return Inertia::render('admin/Transactions', [
            'transactions' => Transaction::query()
                ->with('organization:id,name')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->through(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'organization' => $transaction->organization?->name,
                    'eventType' => $transaction->event_type,
                    'amount' => $transaction->formattedAmount(),
                    'currency' => strtoupper($transaction->currency),
                    'status' => $transaction->status,
                    'createdAt' => $transaction->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function invoices(): Response
    {
        return Inertia::render('admin/Invoices', [
            'invoices' => Invoice::query()
                ->with('organization:id,name')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->through(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'organization' => $invoice->organization?->name,
                    'number' => $invoice->number ?? $invoice->stripe_invoice_id,
                    'total' => $invoice->formattedTotal(),
                    'status' => $invoice->status,
                    'createdAt' => $invoice->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function activity(Request $request): Response
    {
        $query = AuditEvent::query()
            ->with(['actor:id,name,email', 'organization:id,name'])
            ->orderByDesc('id');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }

        $actions = AuditEvent::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('admin/ActivityLogs', [
            'events' => $query->paginate(25)
                ->withQueryString()
                ->through(fn (AuditEvent $event) => [
                    'id' => $event->id,
                    'action' => $event->action,
                    'actor' => $event->actor !== null ? $event->actor->name : 'system',
                    'organization' => $event->organization?->name,
                    'ipAddress' => $event->ip_address,
                    'createdAt' => $event->created_at?->toIso8601String(),
                ]),
            'actions' => $actions,
            'filters' => ['action' => $action],
        ]);
    }

    public function health(): Response
    {
        // Run the checks fresh (writes into the configured result store),
        // then read the latest result per check.
        Artisan::call('health:check', ['--no-interaction' => true]);

        $latest = HealthCheckResultHistoryItem::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->unique('check_name')
            ->values();

        return Inertia::render('admin/SystemHealth', [
            'checks' => $latest->map(fn ($item) => [
                'name' => $item->check_name,
                'label' => $item->check_label,
                'status' => $item->status,
                'summary' => $item->short_summary,
                'checkedAt' => $item->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
