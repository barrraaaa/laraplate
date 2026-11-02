# Customization

## Frontend architecture — build once, reuse everywhere

```
resources/js/
├── components/
│   ├── ui/          # shadcn primitives (Radix base, indigo brand theme)
│   ├── shared/      # empty-state, form-error, logo, theme-toggle
│   └── layouts/     # app-layout, admin-layout, auth-layout
├── features/        # domain slices (dashboard, teams, billing, projects…)
│   └── <your-feature>/
├── pages/           # Inertia pages (app/, admin/, auth/) — pages stay thin
├── hooks/           # use-theme, use-organization (auth/org context)
├── lib/             # cn(), utilities
└── types/           # shared PageProps (auth.user, organization, notifications…)
```

Rules:
- **Never duplicate** a `Button`, dialog, table row or form control —
  components live in `components/ui` + `components/shared`
- Forms use **react-hook-form + zod**; dialogs come from `components/ui/dialog`
- Pages get server data via typed `PageProps`; routes via the typed
  global `route()` (Ziggy — regenerate after route changes:
  `php artisan ziggy:generate --types`)

## Adding a module (copy Projects)

`app/Modules/Projects` is the complete reference slice:

1. **Migration + model** — `organization_id` FK + `BelongsToOrganization`
   trait (scoping + attribution for free), enum casts, factory
2. **Policy** — `app/Modules/*/Policies` registered via `Gate::policy`
   in `AppServiceProvider` (module policies are not auto-discovered)
3. **FormRequests** for input validation
4. **Service** — quota-bearing writes enforced here; controllers stay thin
5. **Controller** — Inertia responses `pages/app/…`, `$this->authorize()`
   per action
6. **Feature** — `features/<name>/` components + a thin page
7. **Routes** — module block in `routes/web.php`
8. **Tests** — Feature tests asserting scoping, authz and quota behavior

## Adding a plan entitlement

1. Extend the plan's `features`/`quotas` in `config/saas.php`
2. `php artisan db:seed --class=PlanSeeder`
3. Enforce with `EntitlementService`: `can()` for flags, `quota()` +
   `overLimit()` for limits — and `record()` for event-style metering

## Adding a permission

1. Add the ability to the right role lists in `saas.roles`
2. Use `$user->can('your.ability')` (organization-scoped via spatie teams)

## Branding

- **Name**: `APP_NAME` (+ `VITE_APP_NAME` follows automatically)
- **Colors**: CSS variables at the bottom of `resources/css/app.css`
  (`--primary`, `--ring` in `:root`/`.dark`) — swap the indigo brand pair
- **Logo**: `components/shared/application-logo.tsx` (inline SVG)
- **Email templates**: notification classes under `app/Notifications` and
  `app/Modules/*/Notifications`; gateway layout via Laravel's mail themes

## Styling conventions

- shadcn/ui primitives + Tailwind utility classes; dark mode via the
  `dark` class (theme-toggle + `use-theme`)
- Empty/loading/error states: `components/shared/empty-state` +
  `components/ui/skeleton`
- Long running actions surface via status toasts (`components/ui/sonner`)

## Keeping merges easy

Custom modules live in `app/Modules/**` + `features/**` + `pages/app/**`.
Core module internals are treated as the framework — extend around them,
not inside them.