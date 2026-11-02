<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Modules\Tenancy\OrganizationContext;
use App\Modules\Tenancy\Support\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Proves the two tenancy invariants from BelongsToOrganization:
 * writes are attributed to the active organization, reads are scoped to it.
 */
class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_records', function ($table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scoped_records');
        app(OrganizationContext::class)->set(null);

        parent::tearDown();
    }

    public function test_created_records_inherit_the_active_organization(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $record = ScopedRecord::query()->create(['name' => 'alpha']);

        $this->assertSame($organization->id, $record->organization_id);
    }

    public function test_queries_only_return_records_of_the_active_organization(): void
    {
        $mine = Organization::factory()->create();
        $theirs = Organization::factory()->create();

        ScopedRecord::query()->create(['name' => 'mine', 'organization_id' => $mine->id]);
        ScopedRecord::query()->create(['name' => 'theirs', 'organization_id' => $theirs->id]);

        app(OrganizationContext::class)->set($mine);

        $this->assertSame(['mine'], ScopedRecord::query()->pluck('name')->all());

        app(OrganizationContext::class)->set($theirs);

        $this->assertSame(['theirs'], ScopedRecord::query()->pluck('name')->all());
    }

    public function test_queries_without_a_context_are_unscoped(): void
    {
        $mine = Organization::factory()->create();
        $theirs = Organization::factory()->create();

        ScopedRecord::query()->create(['name' => 'mine', 'organization_id' => $mine->id]);
        ScopedRecord::query()->create(['name' => 'theirs', 'organization_id' => $theirs->id]);

        $this->assertSame(2, ScopedRecord::query()->count());
    }
}

/**
 * @property int|null $organization_id
 * @property string $name
 */
final class ScopedRecord extends Model
{
    use BelongsToOrganization;

    protected $table = 'scoped_records';

    public $timestamps = false;

    /** @var list<string> */
    protected $guarded = [];
}
