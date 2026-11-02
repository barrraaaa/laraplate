<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_personal_organization_owned_by_the_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $organization = $user->personalOrganization();

        $this->assertNotNull($organization);
        $this->assertTrue($organization->isPersonal());
        $this->assertSame($user->id, $organization->created_by);
        $this->assertSame(OrganizationRole::Owner, $user->organizationRole($organization));
    }

    public function test_each_registered_user_gets_their_own_distinct_personal_organization(): void
    {
        $this->register('alice@example.com');
        $this->post('/logout');

        $this->register('bob@example.com');

        $alice = User::query()->where('email', 'alice@example.com')->firstOrFail();
        $bob = User::query()->where('email', 'bob@example.com')->firstOrFail();

        $aliceOrg = $alice->personalOrganization();
        $bobOrg = $bob->personalOrganization();

        $this->assertNotNull($aliceOrg);
        $this->assertNotNull($bobOrg);
        $this->assertNotSame($aliceOrg->id, $bobOrg->id);
        $this->assertSame(1, $alice->organizations()->where('personal', true)->count());
    }

    public function test_deleting_a_user_also_deletes_their_personal_organization(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationService::class)->createPersonalOrganization($user);

        $user->delete();

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
        $this->assertModelMissing($user);
    }

    private function register(string $email): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }
}
