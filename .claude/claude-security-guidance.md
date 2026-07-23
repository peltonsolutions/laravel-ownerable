# Pelton Solutions security rules

Laravel/Filament multi-tenant SaaS (org `peltonsolutions`). Every account is a
separate customer, so the dominant risk class is **cross-tenant data leakage**,
not classic injection. Flag violations below as HIGH severity.

> This is the org-wide baseline. An app with its own risk surface should extend
> this file rather than replace it.

## Tenant isolation (highest priority)

Isolation comes from `peltonsolutions/laravel-ownerable`: models using the
`CanBePossessed` trait get an `owner` global scope filtering on the polymorphic
`ownerable_type`/`ownerable_id` pair against `Account::getCurrent()`.

Flag:

- **A new model holding tenant-owned data that doesn't use `CanBePossessed`**
  (or a documented per-app alternative such as an `account_uuid`-scoping trait).
  New Filament resource models must be covered.
- **`withoutGlobalScope('owner')` / `withoutGlobalScopes()` in code that already has
  a current tenant** — Filament form `Select->options()`, table queries, Livewire
  lookups, controllers. This bleeds other accounts' records into the UI. The fix is
  `Account::actingAs($account)`, or the closure form
  `Account::actingAs($account, fn () => …)` which restores the previous tenant
  afterwards (even on exception), or simply relying on the scope.
- Settings/detail pages that drop the scope and then re-filter by the current
  account by hand — redundant, and one edit away from a leak.

Legitimate bypasses — do **not** flag these:

- **Owner→possession relationship methods on `Account`/admin models.** These are
  already constrained by `ownerable_id = account.uuid`, and in a context with no
  active tenant (a Super-Admin panel) the scope would AND in `owner_id IS NULL` and
  return zero rows. They correctly read
  `->hasMany(X::class, 'ownerable_id', 'uuid')->withoutGlobalScope('owner')->where('ownerable_type', static::class)`.
- Queued jobs and scheduled commands (no tenant — must set one via `Account::actingAs()`
  before touching data).
- Inbound webhook / OAuth controllers with no session, and sync services matching
  records by external id within a known connection's account.

Any *new* bypass beyond these needs explicit human approval and a cross-account
isolation test — flag it and say so.

## The `booted()` trap

**Never define `booted()` on a model that uses `CanBePossessed`.** PHP gives a class's
own `booted()` precedence over the trait's, which silently disables both owner
assignment on create (`ownerable_type`/`ownerable_id` stay null) and tenant scoping —
a direct data leak, not a style issue. Model event hooks belong in
`protected static function boot()` with `parent::boot()` called first. The same applies
to any other Pelton trait that hooks `booted()`.

## Billing and entitlement gating

Apps bill via Laravel Cashier + Stripe behind `peltonsolutions/laravel-billing`.

- Access is granted **solely** by a valid Stripe subscription
  (`ManagesSubscription::billingActive()` / `subscribed('default')`, true while
  `trialing`). Flag any new access check that grants entitlement from a
  `trial_ends_at` column, Cashier's `onGenericTrial()`, or a hand-rolled flag —
  that is an authorization bypass for paid features.
- Feature/module gates must be enforced **server-side** on the route or action, not
  only by hiding UI. A Filament page or API route for a gated module that lacks its
  module/tier check is a finding.
- Never trust a plan, price id, seat count, or module list submitted from the client.

## Consent and legal gates

Where `peltonsolutions/laravel-policies` is in use, the version re-acceptance gate runs
**before** the billing gate. Flag new routes added to the gate's exclusion list that
aren't genuinely public (health, webhooks, auth entry points).

## General Laravel

- No hardcoded credentials, API keys, or Stripe/AWS secrets in code, config defaults,
  or tests. Runtime secrets come from env / Secrets Manager.
- No raw SQL interpolation of user input (`DB::raw`, `whereRaw`, `orderByRaw`) — bind
  parameters. Sort and filter columns sourced from request input must be allowlisted.
- Blade `{!! !!}` on user- or customer-supplied content is XSS. Custom fields, names,
  and free-text notes are all user-controlled.
- Routes added to the CSRF `except:` list in `bootstrap/app.php` must be webhooks that
  verify a provider signature. A new entry without verification is a finding.
- Public routes taking a record id or email directly need `signed` middleware or a
  single-use token. Public Livewire components must establish the tenant in `boot()`,
  not `mount()`.
- Uploads: validate mime + size, store on a private disk, and never interpolate a
  user-supplied path into `Storage::` calls (path traversal).
- Mass assignment: `$fillable` must never include `ownerable_id`, `ownerable_type`,
  account keys, or approval/status fields that a workflow gates.
- Route-model binding by id is **not** authorization. A bare `Model::find($request->id)`
  in an unscoped context is IDOR.
