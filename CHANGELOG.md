# Changelog

All notable changes to Laraplate are documented in this file.
Format: [Keep a Changelog](https://keepachangelog.com) + [SemVer](https://semver.org).

## [1.0.0] - 2026-11-02

Initial release — the MVP scope of the PRD (`laravel_saas_boilerplate_prd_v1`).

### Foundation
- Laravel 13 / PHP 8.3, Inertia v3 + React 19 + TypeScript, Tailwind 4 +
  shadcn/ui (Radix), Ziggy typed routes, Vite, Geist font, light/dark themes
- `laraplate:status` environment validation; `composer check` quality gates
- CI matrix PHP 8.3/8.4 (SQLite + MySQL), Pint, PHPStan level 5, ESLint

### Identity & Tenancy
- Email/password auth, queued verification, resets, sessions, account deletion
- `SAAS_MODE=single|team|hybrid`; shared-DB tenancy via global scopes +
  auto-attribution; personal-organization anchor for single-user mode
- Organizations, invitations (hashed tokens, expiry/resend/revoke, guest
  registration acceptance), context switching, ownership transfer with
  last-owner protection
- Roles owner/admin/member/viewer mirrored into organization-scoped
  spatie permissions; self-healing permission catalog

### Monetization (Stripe)
- Provider-adapter boundary (BillingGateway) with a Cashier-backed Stripe
  implementation; organizations as the billable owner (polymorphic
  subscriptions)
- Plan catalog from config (free/Pro/Business sample), idempotent seeder
- Checkout, cancel/resume, customer portal; signature-verified,
  idempotent webhooks; local transaction + invoice mirrors
- Entitlements: feature flags, quotas, period-scoped usage recording,
  race-safe increments, limit enforcement with upgrade CTA

### Operations
- Platform admin panel (sidebar layout per the design blueprint):
  dashboard metrics + signups chart, users, organizations, subscriptions,
  transactions, invoices, audit stream, system health (spatie health)
- Audit log with actor + tenant context (survives actor deletion)
- In-app notifications (bell) for payments + welcome; queued mails
- Sanctum personal API tokens with abilities; throttled auth/API routes
- Private file serving through signed URLs (avatar upload)

### Productization
- Example **Projects** module (the documented copy-me pattern incl.
  quota enforcement)
- Demo seeder + `laraplate:demo` (labeled sample data)
- Documentation set: installation, configuration, billing, tenancy,
  customization, testing, deployment, roadmap
- Commercial license template, marketing landing + pricing sample pages,
  terms/privacy placeholders