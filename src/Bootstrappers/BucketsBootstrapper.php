<?php

namespace Versa\TenancyExtension\Bootstrappers;

use Illuminate\Contracts\Foundation\Application;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class BucketsBootstrapper implements TenancyBootstrapper
{
    protected Application $app;
    protected ?string $originalBucket;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->originalBucket = $this->app['config']['filesystems.disks.s3.bucket'];
    }

    public function bootstrap(Tenant $tenant): void
    {
        $bucket = $this->app['config']['tenancy.filesystem.suffix_base'] . $tenant->getTenantKey();
        $bucket = $tenant->bucket ?? $bucket;
        $this->app['config']['filesystems.disks.s3.bucket'] = $bucket;
    }

    public function revert(): void
    {
        $this->app['config']['filesystems.disks.s3.bucket'] = $this->originalBucket;
    }
}