---
name: laravel-emails
description: Polymorphic email-address storage for Laravel (joepages/laravel-emails). Load this skill whenever a task involves attaching, storing, listing, validating, or managing email addresses on Eloquent models — multiple emails per Customer/Order/User, primary email selection, work/billing/personal email types, email verification flags (is_verified, verified_at), bulk email sync from API payloads, nested emails arrays in form requests, email CRUD endpoints, contact-info management, or tenant-aware email migrations. Triggers include "add emails to a model", "primary email", "email types", "emails table", HasEmails, ManagesEmails, EmailDto, EmailRequest, Route::emailRoutes, emails:install. Note - this package stores email ADDRESSES; it does not send mail (use Laravel Mail for sending).
---

# Laravel Emails

Polymorphic email-address storage for Laravel 11/12: attach N email addresses to any Eloquent model via a `morphMany` relation backed by a single `emails` table, with typed classification (`personal`/`work`/`billing`/`other`), single-primary enforcement, verification tracking, JSON metadata, bulk sync, ready-made HTTP CRUD endpoints, and tenancy-aware migration publishing. The core abstractions are a model trait (`HasEmails`), a bound service (`EmailServiceInterface`), and a controller trait (`ManagesEmails`). Mental model: a contact-info address book for your models — it never sends mail.

## Installation & setup

```bash
composer require joepages/laravel-emails
```

If your project resolves this package from a VCS repository instead of Packagist, register it first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/joepages/laravel-emails" }
]
```

The service provider (`Emails\EmailsServiceProvider`) is auto-discovered. Then:

```bash
php artisan emails:install   # publishes config/emails.php + migrations
php artisan migrate
```

Install options: `--force` (overwrite existing files), `--skip-migrations` (publish config only). The installer auto-detects stancl/tenancy (`tenancy()` function + `tenancy.tenant_model` config) and publishes the migration to `database/migrations/tenant/` instead of `database/migrations/` when present.

Notes:

- Migrations are also auto-loaded from the package (`loadMigrationsFrom`), so `php artisan migrate` creates the `emails` table even if you never publish. Publishing matters mainly for multi-tenant apps (tenant migration directory) or customization.
- Config alone can be published with `php artisan vendor:publish --tag=emails-config`. There is no migrations publish tag — only `emails:install` copies migration files.
- No env vars are required. PHP ^8.2, Laravel ^11 or ^12.

### Install this skill into Claude Code

This package ships this skill at `skills/laravel-emails/`. Add to your project `composer.json` so the skill lands in `.claude/skills/` on every install/update:

```json
"scripts": {
    "post-install-cmd": ["@php vendor/joepages/laravel-emails/bin/install-skill"],
    "post-update-cmd": ["@php vendor/joepages/laravel-emails/bin/install-skill"]
}
```

The installer overwrites on every run (the package copy is the source of truth) and no-ops unless your project root contains a `.claude/` directory. Add `.claude/skills/laravel-emails/` to your project `.gitignore`.

## Core API

All classes live under the `Emails\` namespace (PSR-4 root `src/`). No facade is provided — resolve `Emails\Contracts\EmailServiceInterface` from the container (registered as a singleton; `Emails\Contracts\EmailRepositoryInterface` is bound non-singleton to `Emails\Repositories\EmailRepository`).

### Config keys (`config/emails.php`)

| Key | Default | Purpose |
|-----|---------|---------|
| `tenancy_mode` | `'auto'` | `'auto'` detects stancl/tenancy; `'single'`/`'multi'` force the mode. Affects only where `emails:install` publishes migrations. |
| `types` | `['personal', 'work', 'billing', 'other']` | Allowed email types, used by validation. |
| `default_type` | `'personal'` | Type used by `EmailDto` when none supplied. |
| `allow_custom_types` | `true` | `true`: any string type up to 50 chars passes validation. `false`: only values in `types` pass. |

### Model trait — `Emails\Concerns\HasEmails`

Add to any Eloquent model that owns email addresses.

| Method | Returns | Purpose |
|--------|---------|---------|
| `emails()` | `MorphMany` | All `Email` rows for the model. |
| `primaryEmail()` | `MorphOne` | The row where `is_primary = true`. |
| `emailsOfType(string $type)` | `MorphMany` | `emails()` filtered by `type`. |

### Model — `Emails\Models\Email`

Table `emails`. Fillable: `emailable_type`, `emailable_id`, `type`, `is_primary`, `email`, `is_verified`, `verified_at`, `metadata`. Casts: `is_primary`/`is_verified` → bool, `verified_at` → datetime (Carbon), `metadata` → array. Timestamps on; no soft deletes.

| Member | Signature | Purpose |
|--------|-----------|---------|
| Relation | `emailable(): MorphTo` | Parent model. |
| Scope | `Email::primary()` | `where is_primary = true`. |
| Scope | `Email::ofType(string $type)` | Filter by type. |
| Scope | `Email::forModel(Model $model)` | Filter by parent (`getMorphClass()` + key). |
| Scope | `Email::verified()` | `where is_verified = true`. |
| Helper | `markAsPrimary(): bool` | Sets this row primary, bulk-unsets all sibling rows of the same parent. |
| Helper | `markAsVerified(): bool` | Sets `is_verified = true`, `verified_at = now()`, saves. |
| Accessor | `$email->domain` (`?string`) | Part after `@` (`null` if no email/`@`). |
| Factory | `Email::factory()` | `Emails\Database\Factories\EmailFactory`; states: `primary()`, `personal()`, `work()`, `billing()`, `other()`, `verified()`. Factory does not set `emailable_*` — supply them or use `->for($parent, 'emailable')`. |

### DTO — `Emails\DataTransferObjects\EmailDto` (readonly)

```php
public function __construct(
    public string $type,
    public string $email,
    public bool $isPrimary = false,
    public bool $isVerified = false,
    public ?string $verifiedAt = null,
    public ?array $metadata = null,
)
```

| Method | Signature | Purpose |
|--------|-----------|---------|
| `fromRequest()` | `static fromRequest(EmailRequest $request): self` | Builds from `$request->validated()`. |
| `fromArray()` | `static fromArray(array $data): self` | Keys: `email` (required — missing key is a runtime error), `type` (falls back to `config('emails.default_type')`), `is_primary`, `is_verified`, `verified_at`, `metadata`. |
| `toArray()` | `toArray(): array` | Snake-case array of all six fields. |

### Service — `Emails\Contracts\EmailServiceInterface` (singleton; impl `Emails\Services\EmailService`)

| Method | Signature | Behavior |
|--------|-----------|----------|
| `store` | `store(Model $parent, EmailDto $dto): Email` | Creates a row for `$parent`. If `$dto->isPrimary`, first unsets `is_primary` on all of the parent's emails. |
| `update` | `update(Email $email, EmailDto $dto): Email` | Overwrites ALL six DTO fields (no partial update). Unsets sibling primaries only when the DTO is primary and the row was not. Returns `$email->fresh()`. |
| `delete` | `delete(Email $email): bool` | Hard delete. |
| `getForParent` | `getForParent(Model $parent): Collection` | All rows for parent, ordered `is_primary` DESC then `type` ASC. |
| `findForParent` | `findForParent(int $emailId, Model $parent): ?Email` | Row by id scoped to parent; `null` if not owned. |
| `sync` | `sync(Model $parent, array $emailsData): Collection` | Per item: with `id` (and owned by parent) → update; otherwise → create. Rows absent from the payload are deleted — but only when at least one item was kept (empty payload deletes nothing). Returns `getForParent()`. |

### Repository — `Emails\Contracts\EmailRepositoryInterface` (bound to `Emails\Repositories\EmailRepository`)

Lower-level escape hatch: `find(int $id): ?Email`, `create(array $data): Email`, `update(Email $email, array $data): Email`, `delete(Email $email): bool`, `getForParent(Model $parent): Collection`, `findForParent(int $emailId, Model $parent): ?Email`, `unsetPrimaryForParent(Model $parent): void`, `deleteWhereNotIn(Model $parent, array $ids): void`. Prefer the service — `create()` does not manage the primary flag.

### Controller trait — `Emails\Concerns\ManagesEmails`

Host-controller requirements: a `$this->service` exposing `getById(int $id): Model` (used by `protected resolveParentModel(int $parentId): Model` — override it to use `Model::findOrFail()` instead), and the `authorize()` method (Laravel's `AuthorizesRequests` trait) with a policy on the parent model.

| Method | Signature | Behavior |
|--------|-----------|----------|
| `listEmails` | `listEmails(int $parentId): JsonResource` | Authorizes `view` on parent; returns `EmailCollection`. |
| `storeEmail` | `storeEmail(EmailRequest $request, int $parentId): JsonResponse` | Authorizes `update`; creates via service; 201 with `EmailResource`. |
| `updateEmail` | `updateEmail(EmailRequest $request, int $parentId, int $emailId): JsonResource` | Authorizes `update`; 404 if email not owned by parent; returns `EmailResource`. |
| `deleteEmail` | `deleteEmail(int $parentId, int $emailId): JsonResponse` | Authorizes `update`; 404 if not owned; 200 `{"message": "Email deleted successfully."}`. |
| `attachEmail` | `protected attachEmail(Request $request, Model $model): void` | Hook for a base controller's store/update flow: if the request has a non-empty `emails` array, calls `EmailServiceInterface::sync()`. No-op otherwise. Call it yourself after creating/updating the parent. |

### Route macro

`Route::emailRoutes(string $prefix, string $controller)` registers (param name is the singular of `$prefix`; controller methods receive it as a raw `int`, no implicit model binding):

| Method | URI | Controller action |
|--------|-----|-------------------|
| GET | `/{prefix}/{singular}/emails` | `listEmails` |
| POST | `/{prefix}/{singular}/emails` | `storeEmail` |
| PUT | `/{prefix}/{singular}/emails/{email}` | `updateEmail` |
| DELETE | `/{prefix}/{singular}/emails/{email}` | `deleteEmail` |

Routes get no names and no middleware — wrap the call in your own `Route::middleware([...])->group(...)`.

### Validation — `Emails\Http\Requests\EmailRequest`

`authorize()` returns `true` (authorization happens via policies in the trait). `rules()`:

| Field | Rules |
|-------|-------|
| `email` | `required`, `email`, `max:255` |
| `type` | `sometimes`, `string`, then `max:50` (custom types allowed) or `in:<config types>` (disallowed) |
| `is_primary`, `is_verified` | `sometimes`, `boolean` |
| `verified_at` | `nullable`, `date` |
| `metadata` | `nullable`, `array` |

`EmailRequest::embeddedRules(string $prefix = 'emails'): array` (static) returns the same rules namespaced for a nested array — `{$prefix}` is `['sometimes', 'array']`, plus `{$prefix}.*.id` (`sometimes`, `integer`, `exists:emails,id`), `{$prefix}.*.email` (`required`, ...), etc. Spread into a parent FormRequest's `rules()`.

### Resources — `Emails\Http\Resources\EmailResource`, `EmailCollection`

`EmailResource` serializes: `id`, `type`, `is_primary`, `email`, `domain`, `is_verified`, `verified_at`, `metadata`, `created_at`, `updated_at` (the three timestamps as ISO-8601 strings or null). `EmailCollection` wraps a list of `EmailResource` under `data`.

`Emails\Concerns\WithEmailsResource` — trait for your API resources; `protected emailsResource(): array` returns `['emails' => ..., 'primary_email' => ...]` from the `whenLoaded('emails')` / `whenLoaded('primaryEmail')` relations (so eager-load them).

### Artisan command

`emails:install {--force} {--skip-migrations}` — publishes `config/emails.php` and copies migrations to `database/migrations` (or `database/migrations/tenant` when multi-tenant). Existing files are skipped unless `--force`.

### Tenancy helper — `Emails\Services\TenancyResolver`

`isMultiTenant(): bool` — honors `emails.tenancy_mode` (`single`/`multi`), else auto-detects stancl/tenancy. Result is memoized per instance. Used by the install command; rarely needed directly.

### Migration (table `emails`)

`id`; `emailable_type` string + `emailable_id` unsignedBigInteger (indexed together); `type` string(50) default `'personal'` (indexed); `is_primary` bool default false (indexed); `email` string (indexed, NOT unique); `is_verified` bool default false; `verified_at` nullable timestamp; `metadata` nullable json; timestamps.

## Canonical examples

### 1. Attach emails to a model and manage the primary flag

Add the trait to a model, then create addresses through the service (the service is what keeps "one primary per parent" true).

```php
use Emails\Concerns\HasEmails;
use Emails\Contracts\EmailServiceInterface;
use Emails\DataTransferObjects\EmailDto;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasEmails;
}

$service = app(EmailServiceInterface::class);
$customer = Customer::create(['name' => 'Acme Corp']);

$personal = $service->store($customer, new EmailDto(
    type: 'personal',
    email: 'john@example.com',
    isPrimary: true,
));

$work = $service->store($customer, new EmailDto(
    type: 'work',
    email: 'john@company.com',
    isPrimary: true,            // demotes the personal one automatically
));

$customer->fresh()->primaryEmail->email;   // "john@company.com"
$customer->emails()->count();              // 2
$customer->emailsOfType('work')->get();    // the work email
$work->domain;                             // "company.com"
$work->markAsVerified();                   // is_verified=true, verified_at=now()
```

### 2. Full HTTP wiring: routes, controller trait, policy

`resolveParentModel()` defaults to `$this->service->getById($id)`; override it if you don't have such a service. A policy on the parent model is required (`view` for listing, `update` for write operations).

```php
// routes/api.php
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::emailRoutes('customers', \App\Http\Controllers\CustomerController::class);
});

// app/Http/Controllers/CustomerController.php
namespace App\Http\Controllers;

use App\Models\Customer;
use Emails\Concerns\ManagesEmails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    use AuthorizesRequests;
    use ManagesEmails;

    protected function resolveParentModel(int $parentId): Model
    {
        return Customer::findOrFail($parentId);
    }
}
```

`POST /customers/1/emails` with `{"email": "billing@acme.test", "type": "billing"}` → 201 + `EmailResource` body. `PUT /customers/1/emails/5` updates (404 if email 5 belongs to another customer). `DELETE /customers/1/emails/5` → 200 `{"message": "Email deleted successfully."}`.

### 3. Bulk sync of a nested `emails` array

Items with `id` are updated, items without are created, rows missing from the payload are deleted. Each item must carry the FULL field set you want to keep — `update()` overwrites every field from DTO defaults.

```php
use Emails\Contracts\EmailServiceInterface;
use Emails\Http\Requests\EmailRequest;
use Illuminate\Foundation\Http\FormRequest;

// Validate the nested array inside your parent FormRequest:
class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            ...EmailRequest::embeddedRules(),        // or embeddedRules('contact_emails')
        ];
    }
}

// In the controller, after saving the parent:
app(EmailServiceInterface::class)->sync($customer, [
    ['id' => 1, 'email' => 'john.updated@example.com', 'type' => 'personal', 'is_primary' => true],
    ['email' => 'billing@example.com', 'type' => 'billing'],
]);
// Result: email #1 updated and primary, a billing email created,
// every other email of $customer deleted.
```

If your controller participates in a base-controller "attach related data" flow, call `$this->attachEmail($request, $customer)` (from `ManagesEmails`) — it runs the same sync when the request contains a non-empty `emails` key.

### 4. Expose emails in API responses and query them

```php
use App\Models\Customer;
use Emails\Concerns\WithEmailsResource;
use Emails\Models\Email;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    use WithEmailsResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            ...$this->emailsResource(),  // adds 'emails' + 'primary_email' when loaded
        ];
    }
}

new CustomerResource($customer->load(['emails', 'primaryEmail']));

// Standalone queries via scopes:
Email::forModel($customer)->verified()->get();
Email::ofType('billing')->primary()->get();
```

## Events, exceptions & edge cases

- **No custom events, exceptions, mailables, notifications, jobs, or cache usage.** Standard Eloquent model events fire on `Email` create/update/delete via the service — EXCEPT the bulk statements: the sibling-demotion step inside `markAsPrimary()`, `unsetPrimaryForParent()`, and `sync()`'s pruning use query-builder `update()`/`delete()`, which bypass model events and observers (the row's own `save()` inside `markAsPrimary()` DOES fire events).
- **404 aborts**: `updateEmail`/`deleteEmail` call `abort(404, 'Email not found.')` when the email id doesn't belong to the resolved parent. `AuthorizationException` (403) from `authorize()` if the policy denies.
- **`EmailDto` is `readonly`** — assigning to a property throws `Error`. `EmailDto::fromArray()` without an `email` key fails at runtime (undefined key → `TypeError` on the `string $email` parameter); validate first.
- **Updates are full overwrites.** `EmailService::update()`/`sync()` write all six DTO fields. Omitting `is_primary`/`is_verified`/`verified_at`/`metadata` in an update or sync item resets them to `false`/`null`. A primary email updated without `is_primary: true` gets silently demoted; a verified one loses its verification.
- **`sync()` with an empty array deletes nothing** — the prune step (`deleteWhereNotIn`) only runs when at least one item was kept. To clear all emails, delete them explicitly.
- **`attachEmail()` is a no-op** when the request lacks an `emails` key or it's empty — it never wipes existing emails on parent update.
- **No DB uniqueness**: `email` is indexed but not unique, duplicates per parent are allowed, and nothing at the DB level enforces a single `is_primary` per parent — only the service/`markAsPrimary()` path does. Direct `Email::create()` bypasses that invariant.
- **Hard deletes** — the model has no `SoftDeletes`; deleting a parent does NOT cascade (no FK on `emailable_id`); orphan rows are your responsibility.
- **Ordering**: `getForParent()` returns primary first, then alphabetical by `type`.
- **Route macro params are raw ints** — the `{customer}`-style segment is not implicitly bound to a model; trait methods receive `int $parentId`.
- **Tenancy** only changes where `emails:install` copies migrations; runtime queries are not tenant-scoped by the package (stancl/tenancy's DB-per-tenant model handles that naturally).

## Common mistakes

- ❌ Using this package to send mail or log sent messages → ✅ it only stores email addresses on models; send with Laravel's `Mail` facade and read addresses from `$model->primaryEmail->email`.
- ❌ Partial updates: `sync($customer, [['id' => 5, 'email' => 'new@x.test']])` keeping flags intact → ✅ include `type`, `is_primary`, `is_verified`, `verified_at`, `metadata` in each item, or the omitted ones reset to defaults (`false`/`null`).
- ❌ `sync($customer, [])` to remove all emails → ✅ empty payload is a no-op for deletion; loop `$service->delete($email)` or `Email::forModel($customer)->get()->each->delete()`.
- ❌ `Email::create(['is_primary' => true, ...])` directly → ✅ use `EmailServiceInterface::store()` or `$email->markAsPrimary()` so sibling primaries get unset.
- ❌ Using `ManagesEmails` in a plain controller and hitting "Undefined property: $service" → ✅ override `protected function resolveParentModel(int $parentId): Model` (e.g. `Customer::findOrFail($parentId)`), or provide `$this->service` with a `getById(int)` method; also `use AuthorizesRequests` and define a policy on the parent.
- ❌ `php artisan vendor:publish --tag=emails-migrations` → ✅ that tag doesn't exist; migrations auto-load from the package, and `emails:install` is what copies them into `database/migrations[/tenant]`. The only publish tag is `emails-config`.
- ❌ Expecting `$resource->emailsResource()` to always include emails → ✅ it uses `whenLoaded()`; eager-load `emails` and `primaryEmail` or the keys are omitted from the JSON.
- ❌ Counting on validation to reject unknown types by default → ✅ `allow_custom_types` defaults to `true` (any string ≤50 chars passes); set it to `false` to restrict to the `types` list.

## Version notes

Documented from the current 1.x source: PHP ^8.2, Laravel (illuminate/*) ^11 or ^12. No deprecations, no version-gated features, no facade, no events API. The package is install-discoverable via Laravel package auto-discovery (`extra.laravel.providers`). Multi-tenancy support targets stancl/tenancy detection only and affects migration placement, not query scoping.
