<?php

namespace App\Modules\Tenancy\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOwnershipRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('memberships', 'user_id')->where('organization_id', $organization->id),
            ],
        ];
    }
}
