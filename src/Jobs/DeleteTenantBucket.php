<?php

namespace Versa\TenancyExtension\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Versa\TenancyExtension\Bucket;

class DeleteTenantBucket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected TenantWithDatabase|Model $tenant;

    public function __construct(TenantWithDatabase $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        (new Bucket($this->tenant))->deleteTenantBucket();
    }

    public function tags(): array
    {
        return [
            'tenant:' . $this->tenant->id,
        ];
    }
}
