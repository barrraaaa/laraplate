<?php

namespace App\Modules\Audit\Services;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Single write-path for audit events. Tenant + actor context are captured
 * implicitly so call sites stay one-liners; queue-independent (audit rows
 * are cheap inserts, and security events must never be lost to a queue).
 */
final class Auditing
{
    public function record(
        string $action,
        ?Organization $organization = null,
        ?object $subject = null,
        array $metadata = [],
        ?User $actor = null,
    ): AuditEvent {
        $actor ??= User::query()->find(Auth::id());

        // The actor name/email is captured in metadata so the audit trail
        // survives actor deletion (the actor_id FK nulls on delete).
        if ($actor !== null) {
            $metadata += [
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
            ];
        }

        return DB::transaction(fn (): AuditEvent => AuditEvent::create([
            'organization_id' => $organization?->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 512),
            'created_at' => now(),
        ]));
    }
}
