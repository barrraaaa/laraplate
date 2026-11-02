<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use Database\Factories\Auth\MagicLoginFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single-use, expiring passwordless login link (ShipFast-style magic
 * link auth). Tokens are hashed at rest like invitations.
 *
 * @property int $id
 * @property string $email
 * @property string $token_hash
 * @property int|null $user_id
 * @property Carbon|null $used_at
 * @property Carbon $expires_at
 * @property-read User|null $user
 */
#[Fillable(['email', 'token_hash', 'user_id', 'used_at', 'expires_at'])]
class MagicLogin extends Model
{
    /** @use HasFactory<MagicLoginFactory> */
    use HasFactory;

    /**
     * Module factories are not auto-discoverable by the default path
     * guesser — resolve explicitly.
     */
    protected static function newFactory(): MagicLoginFactory
    {
        return MagicLoginFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
