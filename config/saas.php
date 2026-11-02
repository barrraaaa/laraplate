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
        'webhooks' => true,
        'audit_logs' => true,
        'social_login' => true,
        'magic_links' => true,
        'blog' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth extras
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'magic_link_ttl_minutes' => (int) env('SAAS_MAGIC_LINK_TTL', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Community & store (sales page wiring)
    |--------------------------------------------------------------------------
    */

    'community' => [
        'discord_url' => env('DISCORD_INVITE_URL'),
        'leaderboard_url' => env('LEADERBOARD_URL'),
    ],

    'store' => [
        'starter_url' => env('STORE_STARTER_URL'),
        'all_in_url' => env('STORE_ALL_IN_URL'),
    ],

    'seo' => [
        'title' => env('SEO_TITLE', 'Laraplate — Laravel SaaS Boilerplate'),
        'description' => env('SEO_DESCRIPTION', 'Production-oriented Laravel SaaS boilerplate: tenancy, Stripe billing, admin panel and more. Pay once — build unlimited projects.'),
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
        'alert_threshold' => (float) env('SAAS_USAGE_ALERT_THRESHOLD', 0.8),
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
    | Outbound webhooks (PRD 5.8 P1)
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'events' => [
            'user.created' => 'A new user registered.',
            'user.deleted' => 'A user deleted their account.',
            'organization.created' => 'A shared organization was created.',
            'organization.member_joined' => 'A member joined an organization.',
            'payment.succeeded' => 'A payment was received.',
            'payment.failed' => 'A payment failed.',
        ],
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
