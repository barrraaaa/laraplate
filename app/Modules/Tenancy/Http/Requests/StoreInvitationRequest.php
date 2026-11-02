<?php

namespace App\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            // Owners come into existence via ownership transfer only —
            // inviting one directly would break single-owner invariants.
            'role' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ];
    }
}
