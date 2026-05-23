# laravel-ownerable

A Laravel package by [Pelton Solutions](https://www.nathanpelton.com) that makes it easy to attach models to an owner (tenant) and automatically scope queries to that owner.

A common use case is multi-tenant applications — for example, a `BlogPost` that belongs to an `Account`. The package auto-assigns the owner on creation and filters all queries to the current owner.

## Requirements

- PHP ^8.4
- Laravel ^11.0

## Installation

Install via Composer:

```bash
composer require peltonsolutions/laravel-ownerable
```

Publish the config file:

```bash
php artisan vendor:publish --provider="PeltonSolutions\LaravelOwnerable\LaravelOwnerableServiceProvider"
```

This creates `config/ownerable.php`:

```php
return [
    'owner' => env('PELTON_SOLUTIONS_OWNER_CLASS'),
];
```

Set the owner class in your `.env`:

```env
PELTON_SOLUTIONS_OWNER_CLASS=App\Models\Account
```

## Usage

### 1. Implement `CanHavePossessions` on the owner model

The owner model (e.g. `Account`) must implement the `CanHavePossessions` contract:

```php
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;

class Account extends Model implements CanHavePossessions
{
    // Return the currently active owner instance (e.g. from session or auth)
    public static function getCurrent(): ?static
    {
        return static::find(session('account_id'));
    }

    // Associate a possessed model with this owner
    public function addPossession(CanBePossessed $possession)
    {
        $possession->ownerable()->associate($this)->save();
    }

    public function getKey()
    {
        return $this->id;
    }
}
```

### 2. Use the `CanBePossessed` trait on owned models

Add the `CanBePossessed` trait (and optionally implement the contract) to any model that should be scoped to an owner. The model must have `ownerable_id` and `ownerable_type` columns (polymorphic morph).

```php
use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed as CanBePossessedContract;
use PeltonSolutions\LaravelOwnerable\Traits\CanBePossessed;

class BlogPost extends Model implements CanBePossessedContract
{
    use CanBePossessed;
}
```

### What happens automatically

- **On creation:** If the model has no owner yet and a current owner is available, the owner is automatically associated via `ownerable()->associate($owner)`.
- **On all queries:** A global scope filters results to only records owned by the current owner. If no current owner is found, the query returns no results (`ownerable_id = null`).

### Migration example

```php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->nullableMorphs('ownerable');
    // ... other columns
    $table->timestamps();
});
```

### Bypassing the global scope

Use `withoutGlobalScope` when you need to query across all owners:

```php
BlogPost::withoutGlobalScope('owner')->get();
```

## Testing

```bash
composer test
```

The test suite uses [Pest](https://pestphp.com/) and covers the `CanBePossessed` trait and observer — including owner resolution, query scoping, and auto-association on creation.

## Security

If you discover any security-related issues, please email [security@peltonsolutions.com](mailto:security@peltonsolutions.com) instead of using the issue tracker.

## Credits

- [Nathan Pelton](https://www.nathanpelton.com)

## License

laravel-ownerable is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
