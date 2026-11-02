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
    | Plans (sample catalog — replace with your own)
    |--------------------------------------------------------------------------
    |
    | Seeded into the plans table by PlanSeeder (idempotent — re-seed after
    | edits). Sample data, clearly marked for buyers. Paid plans reference
    | Stripe price ids created in your Stripe dashboard (or via Cashier
    | CLI); a plan with no price ids is free and needs no Stripe checkout.
    |
    */

    'plans' => [
        'free_slug' => 'free',

        'catalog' => [
            [
                'name' => 'Starter',
                'slug' => 'free',
                'description' => 'For trying things out — no credit card required.',
                'monthly_price_id' => null,
                'yearly_price_id' => null,
                'features' => [
                    'features' => [
                        'projects' => true,
                        'attachments' => false,
                    ],
                    'quotas' => [
                        'projects.max' => 3,
                        'storage.mb' => 100,
                    ],
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For shipping real products.',
                'monthly_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'yearly_price_id' => env('STRIPE_PRICE_PRO_YEARLY'),
                'features' => [
                    'features' => [
                        'projects' => true,
                        'attachments' => true,
                    ],
                    'quotas' => [
                        'projects.max' => 25,
                        'storage.mb' => 10240,
                    ],
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For teams at scale.',
                'monthly_price_id' => env('STRIPE_PRICE_BUSINESS_MONTHLY'),
                'yearly_price_id' => env('STRIPE_PRICE_BUSINESS_YEARLY'),
                'features' => [
                    'features' => [
                        'projects' => true,
                        'attachments' => true,
                    ],
                    'quotas' => [
                        'projects.max' => null,
                        'storage.mb' => 102400,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage metering
    |--------------------------------------------------------------------------
    */

    'usage' => [
        'period' => env('SAAS_USAGE_PERIOD', 'monthly'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API tokens
    |--------------------------------------------------------------------------
    */

    'api_tokens' => [
        'abilities' => ['read', 'write'],
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
