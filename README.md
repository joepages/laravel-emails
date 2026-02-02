# Laravel Emails

[![Tests](https://github.com/joepages/laravel-emails/actions/workflows/tests.yml/badge.svg)](https://github.com/joepages/laravel-emails/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/joepages/laravel-emails.svg)](https://packagist.org/packages/joepages/laravel-emails)
[![License](https://img.shields.io/packagist/l/joepages/laravel-emails.svg)](https://packagist.org/packages/joepages/laravel-emails)

Polymorphic email addresses for Laravel. Attach multiple email addresses to any Eloquent model with full CRUD, bulk sync, primary management, verification tracking, and multi-tenancy awareness.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require joepages/laravel-emails
```

Run the install command to publish the config and migrations:

```bash
php artisan emails:install
php artisan migrate
```

The installer auto-detects [stancl/tenancy](https://tenancyforlaravel.com/) and publishes migrations to `database/migrations/tenant/` when present.

### Install options

```bash
php artisan emails:install --force            # Overwrite existing files
php artisan emails:install --skip-migrations  # Only publish config
```

## Quick Start

### 1. Add the trait to your model

```php
use Emails\Concerns\HasEmails;

class Facility extends Model
{
    use HasEmails;
}
```

### 2. Add the controller trait

```php
use Emails\Concerns\ManagesEmails;

class FacilityController extends BaseApiController
{
    use ManagesEmails;
}
```

### 3. Register routes

```php
Route::emailRoutes('facilities', FacilityController::class);
```

This registers the following routes:

| Method | URI | Action |
|--------|-----|--------|
| GET | `/facilities/{facility}/emails` | `listEmails` |
| POST | `/facilities/{facility}/emails` | `storeEmail` |
| PUT | `/facilities/{facility}/emails/{email}` | `updateEmail` |
| DELETE | `/facilities/{facility}/emails/{email}` | `deleteEmail` |

## Model Trait API

The `HasEmails` trait provides three relationships on your model:

```php
$facility->emails;                    // All emails (MorphMany)
$facility->primaryEmail;              // Primary email (MorphOne)
$facility->emailsOfType('work');      // Filtered by type (MorphMany)
```

## Email Model

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `type` | string | Email type (`personal`, `work`, `billing`, `other`) |
| `is_primary` | boolean | Whether this is the primary email |
| `email` | string | The email address |
| `is_verified` | boolean | Whether the email has been verified |
| `verified_at` | datetime\|null | When the email was verified |
| `metadata` | array\|null | Custom JSON data |

### Scopes

```php
Email::primary()->get();              // Only primary emails
Email::ofType('work')->get();         // Filter by type
Email::forModel($facility)->get();    // All emails for a specific model
Email::verified()->get();             // Only verified emails
```

### Helpers

```php
$email->markAsPrimary();     // Sets as primary, unsets all others for the same parent
$email->markAsVerified();    // Sets is_verified=true and verified_at=now
$email->domain;              // "example.com" (domain part of the address)
```

## Controller Trait

The `ManagesEmails` trait provides two integration modes:

### Standalone CRUD

Use the `storeEmail`, `updateEmail`, `deleteEmail`, and `listEmails` methods directly via the route macro.

### Bulk Sync via BaseApiController

When your controller extends `BaseApiController`, the `attachEmail()` method is called automatically during `store()` and `update()`. Send an `emails` array in the request body:

```json
{
  "name": "Main Facility",
  "emails": [
    {
      "id": 1,
      "email": "updated@example.com"
    },
    {
      "email": "billing@example.com",
      "type": "billing",
      "is_primary": true
    }
  ]
}
```

- Records **with an `id`** are updated
- Records **without an `id`** are created
- Existing records **not included** in the array are deleted

## API Resource

Add emails to your JSON responses:

```php
use Emails\Concerns\WithEmailsResource;

class FacilityResource extends JsonResource
{
    use WithEmailsResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            ...$this->emailsResource(),
        ];
    }
}
```

## Validation

The `EmailRequest` form request validates:

| Field | Rules |
|-------|-------|
| `email` | required, email, max:255 |
| `type` | sometimes, string (validated against config when `allow_custom_types` is false) |
| `is_primary` | sometimes, boolean |
| `is_verified` | sometimes, boolean |
| `verified_at` | nullable, date |
| `metadata` | nullable, array |

## Configuration

```php
// config/emails.php

return [
    // 'auto' detects stancl/tenancy, 'single' or 'multi' to force
    'tenancy_mode' => 'auto',

    // Allowed email types
    'types' => ['personal', 'work', 'billing', 'other'],

    // Default type when none specified
    'default_type' => 'personal',

    // When false, only types in the 'types' array are accepted
    'allow_custom_types' => true,
];
```

## Database Schema

```sql
CREATE TABLE emails (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    emailable_type  VARCHAR(255) NOT NULL,
    emailable_id    BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(50) DEFAULT 'personal',
    is_primary      BOOLEAN DEFAULT FALSE,
    email           VARCHAR(255) NOT NULL,
    is_verified     BOOLEAN DEFAULT FALSE,
    verified_at     TIMESTAMP NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX (emailable_type, emailable_id),
    INDEX (type),
    INDEX (is_primary),
    INDEX (email)
);
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
