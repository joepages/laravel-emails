# Emails Package

Polymorphic emails package for Laravel. Attach N emails to any Eloquent model.

## Installation

```bash
composer require your-vendor/emails
php artisan emails:install
php artisan migrate
```

## Usage

### Add the trait to your model

```php
use Emails\Concerns\HasEmails;

class Facility extends Model
{
    use HasEmails;
}
```

### Register routes in your route file

```php
use App\Http\Controllers\Api\FacilityController;

Route::emailRoutes('facilities', FacilityController::class);
```

### Add the controller trait

```php
use Emails\Concerns\ManagesEmails;

class FacilityController extends BaseApiController
{
    use ManagesEmails;
}
```

### Add emails to your API resource

```php
use Emails\Concerns\WithEmailsResource;

class FacilityResource extends BaseResource
{
    use WithEmailsResource;

    public function toArray($request): array
    {
        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
        ], $this->emailsResource());
    }
}
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=emails-config
```

## Testing

```bash
composer test
```
