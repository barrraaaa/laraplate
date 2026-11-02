<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('billing.show'))->assertRedirect(route('login'));
    }

    public function test_verified_user_sees_the_billing_area_with_the_free_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('billing.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/Billing/Index')
                ->has('plans', 3)
                ->where('currentPlan.slug', 'free')
                ->where('stripeConfigured', false)
                ->where('canManage', true));
    }
}
