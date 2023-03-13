<h1>Tenancy Extension</h1>

A simple extension to Tenancy for Laravel package, which provides a simple way to manage AWS S3 buckets for each tenant.

## Installation

Require the package using composer:

```bash
composer require versa/tenancy-extension
```

## Usage

### 1. Filesystem Configuration

Ensure that you have a filesystem configuration for your S3 bucket. For example:

**File:** `config/filesystems.php`
```php
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
```

### 2. Tenancy Configuration

Add the `BucketsBootstrapper::class` to the tenancy config file under `bootstrappers`.

**File:** `config/tenancy.php`
```php
    'bootstrappers' => [
        // ...
        Versa\TenancyExtension\Bootstrappers\BucketsBootstrapper::class,
    ],
```

Make sure that the `s3` line is commented in `tenancy.filesystem.disks` config.

**File:** `config/tenancy.php`
```php
    'filesystems' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3', // Make sure this stays commented
        ],
    ],
```

### 3. Job Pipeline Configuration

Add `Versa\TenancyExtension\Jobs\CreateTenantBucket` & `Versa\TenancyExtension\Jobs\DeleteTenantBucket` to the `JobPipeline::make()`. This will ensure that the bucket is created and deleted when a tenant is created or deleted.

**File:** `app/Providers/TenancyServiceProviders.php`
```php
    use Versa\TenancyExtension\Jobs\CreateTenantBucket;
    use Versa\TenancyExtension\Jobs\DeleteTenantBucket;

    // ...

    public function events(): array
    {
        return [
            // ...
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    Jobs\SeedDatabase::class,

                    CreateTenantBucket::class, // <-- Add this line

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],
            // ...
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,

                    DeleteTenantBucket::class, // <-- Add this line
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],
            // ...
        ];
    }
```

Enjoy! 😁
