<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Transaction;
use App\Modules\Blog\Models\Post;
use App\Modules\Entitlements\Models\UsageRecord;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

/**
 * SAMPLE DATA — everything this seeder creates is demo data, clearly
 * labeled for the admin screens and the demo journey. Never run in
 * production against real customers (the --fresh flag only removes
 * records this seeder owns).
 */
class DemoSeeder extends Seeder
{
    private const SAMPLE_EMAILS = [
        'demo-owner@example.com',
        'demo-admin@example.com',
        'demo-member@example.com',
        'demo-billing@example.com',
    ];

    public function run(bool $fresh = false): void
    {
        if ($fresh) {
            $this->wipe();
        }

        if (User::query()->where('email', self::SAMPLE_EMAILS[0])->exists()) {
            (new ConsoleOutput)->writeln('  <comment>Demo data already present — skipped (use --fresh to regenerate).</comment>');

            return;
        }

        $sampleOrganizations = [
            ['name' => 'Acme Inc (Demo)', 'email' => 'demo-owner@example.com', 'sub' => ['pro', 'active']],
            ['name' => 'Aurora Labs (Demo)', 'email' => 'demo-admin@example.com', 'sub' => ['business', 'trialing']],
            ['name' => 'Byteworks (Demo)', 'email' => 'demo-billing@example.com', 'sub' => ['pro', 'canceled']],
        ];

        foreach ($sampleOrganizations as $index => $sample) {
            $owner = $this->user($sample['email']);
            $organization = app(OrganizationService::class)->createTeamOrganization($owner, [
                'name' => $sample['name'],
            ]);

            // A second member for membership-UI depth.
            $member = $this->user('demo-member@example.com');
            app(MembershipService::class)->join($organization, $member, OrganizationRole::Member);

            $this->seedUsage($organization);
            $this->seedBilling($organization, $sample['sub'][0], $sample['sub'][1], $index);
        }

        $this->seedBlogPosts();
    }

    private function seedBlogPosts(): void
    {
        if (Post::query()->exists()) {
            return;
        }

        $author = User::query()->where('email', 'demo@laraplate.test')->first()
            ?? User::query()->first();

        $posts = [
            [
                'title' => 'Ship your first SaaS weekend plan',
                'excerpt' => 'A practical checklist from `laraplate:status` to the first paid checkout.',
                'body' => "The boilerplate removes the boilerplate.\n\n1. Install and run laraplate:status.\n2. Wire Stripe keys and price ids.\n3. Configure SAAS_MODE.\n4. Replace the Projects module with your own product.\n\nFrom a fresh clone to a live checkout this is an afternoon of work, not a month.",
            ],
            [
                'title' => 'Single-user vs team mode — how to choose',
                'excerpt' => 'The tenancy modes explained, with the hybrid sweet spot.',
                'body' => "SAAS_MODE=single hides all organization UI while keeping the tenancy anchor.\n\nTeam mode builds workspace collaboration; hybrid gives every user both.\n\nThe billing owner is always the organization, so switching modes later never migrates data.",
            ],
            [
                'title' => 'Tenancy isolation: why tampering fails',
                'excerpt' => 'Global scopes plus automatic attribution, walked through.',
                'body' => "BelongsToOrganization does two things: every read is scoped to the active organization, and every write inherits organization_id.\n\nCross-tenant tests replay the classic attacks: swapping IDs in URLs, patch payloads and route hints. They all 404 or 403.\n\nThat, plus last-owner protection, is the whole isolation story.",
            ],
        ];

        foreach ($posts as $post) {
            Post::create([
                'author_id' => $author?->id,
                'title' => $post['title'],
                'slug' => Str::slug($post['title']),
                'excerpt' => $post['excerpt'],
                'body' => $post['body'],
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 20)),
                'meta_description' => $post['excerpt'],
            ]);
        }
    }

    private function wipe(): void
    {
        $sampleIds = User::query()->whereIn('email', self::SAMPLE_EMAILS)->pluck('id');
        $organizationIds = Organization::query()
            ->whereIn('created_by', $sampleIds)
            ->pluck('id');

        DB::transaction(function () use ($sampleIds, $organizationIds): void {
            UsageRecord::query()->whereIn('organization_id', $organizationIds)->delete();
            Subscription::query()->whereIn('billable_id', $organizationIds)
                ->where('billable_type', Organization::class)->delete();
            Invoice::query()->whereIn('organization_id', $organizationIds)->delete();
            Transaction::query()->whereIn('organization_id', $organizationIds)->delete();
            Organization::query()->whereIn('id', $organizationIds)->forceDelete();
            User::query()->whereIn('id', $sampleIds)->delete();
        });
    }

    private function user(string $email): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => ucfirst(str_replace('-', ' ', strtok($email, '@'))),
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
    }

    private function seedUsage(Organization $organization): void
    {
        UsageRecord::query()->create([
            'organization_id' => $organization->id,
            'metric' => 'storage.mb',
            'quantity' => random_int(20, 300),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);
    }

    private function seedBilling(Organization $organization, string $planSlug, string $status, int $offset): void
    {
        $plan = Plan::query()->where('slug', $planSlug)->first();
        $price = $plan?->monthly_price_id;

        if ($price === null) {
            $price = 'price_demo_'.$planSlug;
        }

        Subscription::query()->create([
            'billable_type' => Organization::class,
            'billable_id' => $organization->id,
            'type' => 'default',
            'stripe_id' => 'sub_demo_'.($offset + 1),
            'stripe_status' => $status === 'trialing' ? 'trialing' : ($status === 'canceled' ? 'canceled' : 'active'),
            'stripe_price' => $price,
            'quantity' => 1,
            'trial_ends_at' => $status === 'trialing' ? now()->addDays(7) : null,
            'ends_at' => $status === 'canceled' ? now()->addDays(10) : null,
        ]);

        try {
            $amounts = [1900, 4900, 9900];

            foreach (range(1, 3) as $invoiceIndex) {
                $paid = $invoiceIndex < 3 || $status !== 'canceled';

                Invoice::query()->create([
                    'organization_id' => $organization->id,
                    'stripe_invoice_id' => 'in_demo_'.($offset + 1).'_'.$invoiceIndex,
                    'number' => 'DEMO-'.($offset + 1).'00'.$invoiceIndex,
                    'amount_due' => $amounts[$offset % 3],
                    'amount_paid' => $paid ? $amounts[$offset % 3] : 0,
                    'currency' => 'usd',
                    'status' => $paid ? 'paid' : 'open',
                    'period_start' => now()->subMonths(4 - $invoiceIndex)->startOfMonth(),
                    'period_end' => now()->subMonths(3 - $invoiceIndex)->startOfMonth(),
                    'paid_at' => $paid ? now()->subMonths(3 - $invoiceIndex) : null,
                ]);
            }

            Transaction::query()->create([
                'organization_id' => $organization->id,
                'stripe_event_id' => 'evt_demo_'.($offset + 1),
                'event_type' => $status === 'canceled' ? 'invoice.payment_failed' : 'invoice.payment_succeeded',
                'amount' => $status === 'canceled' ? 0 : $amounts[$offset % 3],
                'currency' => 'usd',
                'status' => $status === 'canceled' ? 'failed' : 'succeeded',
                'payload' => ['demo' => true],
            ]);
        } catch (Throwable) {
            // Seeding stays best-effort on constraint clashes after --fresh.
        }
    }
}
