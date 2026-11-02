<?php

namespace App\Modules\Audit\Models;

use App\Models\Organization;
use App\Models\User;
use Database\Factories\Audit\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Security-relevant audit event (PRD 5.9) — login/security, billing and
 * membership actions, always carrying actor + tenant context.
 *
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $actor_id
 * @property string $action
 * @property int|null $subject_id
 * @property string|null $subject_type
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read Organization|null $organization
 * @property-read User|null $actor
 */
#[Fillable(['organization_id', 'actor_id', 'action', 'subject_id', 'subject_type', 'metadata', 'ip_address', 'user_agent'])]
class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
