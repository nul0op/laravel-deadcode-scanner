## Deadcode Scanner (Laravel)

Automatic dead code and route scanner for Laravel applications.

### Installation

```bash
composer require zalanihir/deadcode-scanner --dev
```

Laravel auto-discovers the service provider via `extra.laravel.providers`.

Optionally publish the config:

```bash
php artisan vendor:publish --tag=deadcode-config
```

### Usage

```bash
php artisan deadcode:scan
```

Options:

- `--details` Show counts and extra info
- `--json` Output JSON report to stdout

### What it checks

- Routes pointing to missing controllers/methods
- Unused public controller methods under `app/Http/Controllers`
- Unused Blade views (`resources/views`)
- Undefined routes referenced in Blade via `route('name')`

### Ignoring items

After publishing the config (`config/deadcode.php`):

```php
return [
    'ignore' => [
        'routes' => [
            // route names or URIs
            'login',
            'healthz',
        ],
        'controllers' => [
            // fully-qualified controller classes
            App\Http\Controllers\Internal\WebhookController::class,
        ],
        'controller_methods' => [
            // method names or FQCN::method
            'helper',
            App\Http\Controllers\UserController::class . '::legacy',
        ],
        'views' => [
            // dot-notation view names
            'errors::404',
            'emails.welcome',
        ],
    ],
];
```

### Notes

- Heuristics are conservative; review before deleting code.
- Scans only literal references (no runtime/generated view names).



