<?php

/*
|--------------------------------------------------------------------------
| Laraplate SaaS configuration
|--------------------------------------------------------------------------
|
| Product behavior is configured here. Buyers should not need to edit core
| code for common switches — see the documentation for each key.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Application mode
    |--------------------------------------------------------------------------
    |
    | Supported modes:
    |
    |   single  Single-user SaaS. The organization layer is invisible —
    |           every user silently owns a "personal" organization.
    |
    |   team    Team-based SaaS. Users create or join shared organizations;
    |           no invisible personal organization is created.
    |
    |   hybrid  Both — each user keeps a personal organization and can
    |           additionally create or join team organizations.
    |
    | The `organizations` feature must be enabled in team/hybrid modes.
    | Referencing a disabled feature throws
    | App\Modules\Core\Exceptions\FeatureDisabledException.
    |
    */

    'mode' => env('SAAS_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Feature modules
    |--------------------------------------------------------------------------
    |
    | Modules can be enabled or disabled independently. Disabling a module
    | must not silently corrupt existing data; features are resolved via the
    | FeatureManager facade (App\Modules\Core\FeatureManager).
    |
    */

    'features' => [
        'organizations' => true,
        'billing' => true,
        'usage_limits' => true,
        'api_tokens' => true,
        'webhooks' => false,
        'audit_logs' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing provider
    |--------------------------------------------------------------------------
    |
    | Laravel Cashier ships with Stripe. The provider adapter boundary
    | (App\Modules\Billing\Providers) keeps a second provider addable
    | without touching domain code.
    |
    */

    'billing' => [
        'provider' => env('SAAS_BILLING_PROVIDER', 'stripe'),

        // Stripe credentials — cached safely through config so the
        // laraplate:status command can validate them in config-cached
        // deployments too.
        'stripe' => [
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    'invitations' => [
        'expiry_days' => (int) env('SAAS_INVITATION_EXPIRY_DAYS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles & permissions
    |--------------------------------------------------------------------------
    |
    | The granular permission catalog, assigned to organization roles via
    | spatie/laravel-permission (organization-scoped). The curated source
    | of truth remains memberships.role — these are the capabilities each
    | baseline role conveys; P2/custom roles map through the same model.
    |
    */

    'roles' => [
        'organization-owner' => [
            'organizations.manage',
            'organizations.transfer',
            'organizations.delete',
            'members.manage',
            'invitations.manage',
            'billing.manage',
            'api.tokens.manage',
        ],
        'organization-admin' => [
            'organizations.manage',
            'members.manage',
            'invitations.manage',
            'billing.manage',
            'api.tokens.manage',
        ],
        'organization-member' => [
            'projects.create',
            'projects.view',
        ],
        'organization-viewer' => [
            'projects.view',
        ],
    ],

];
