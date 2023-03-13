<?php

namespace Rayo\TenancyExtension\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rayo\TenancyExtension\Bucket;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class CreateTenantBucket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected TenantWithDatabase|Model $tenant;

    public function __construct(TenantWithDatabase $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        (new Bucket($this->tenant))->createTenantBucket()->getBucketName();
    }

    public function tags(): array
    {
        return [
            'tenant:' . $this->tenant->id,
        ];
    }
}
