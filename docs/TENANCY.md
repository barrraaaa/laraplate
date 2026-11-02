# Tenancy

## Modes (`SAAS_MODE`)

| Mode | Behavior |
|---|---|
| `single` | Single-user SaaS. The organization layer is invisible — every user silently owns a **personal organization**. Organization routes 404. |
| `team` | Team SaaS. Users create/join shared organizations; the personal organization exists only as the invisible isolation anchor until the user joins a team. |
| `hybrid` | Both: personal workspace + shared organizations, context switching between them. |

Shared-organization routes are gated by `EnsureOrganizationsEnabled`
(404 in single mode); the UI reads the shared `saas.mode` prop.

## Isolation model

- **Shared database, logical scoping** (no multi-DB).
- Every tenant-owned model uses the `BelongsToOrganization` trait:
  1. ```reads``` are scoped by `OrganizationScope` to the active organization;
  2. ```writes``` inherit `organization_id` from `OrganizationContext`
     automatically.
- `SetOrganizationContext` middleware resolves the active organization per
  request: explicit session selection wins, otherwise the personal org.
- The **billing owner** is always an organization (personal in single
  mode) — subscriptions, invoices, usage and entitlements are all tenant
  scoped.
- Cross-tenant tests (`TenantScopingTest`, `TenantIsolationTest`) lock
  these invariants: foreign ids cannot be reached by tampering.

## Context switching

`POST /organizations/{id}/switch` stores `current_organization_id` in the
session. The switcher in the app shell lists every organization the user
belongs to. Authorization (policies + spatie team scoping) always
evaluates inside the **active** organization.

## Organizations, members, invitations

- Roles: `owner` > `admin` > `member` > `viewer`
- Invitations: hashed tokens, expiry (config `invitations.expiry_days`),
  resend/revoke, guest flow completes acceptance after registration
- Ownership transfer: owner → member (owner becomes admin); the **last
  owner cannot be removed/leave**
- Archiving is a soft delete; personal organizations cannot be archived

## Extending

Adding a tenant-scoped model = add `use BelongsToOrganization;` + an
`organization_id` FK. Policy checks + middleware handle the rest.