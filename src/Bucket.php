<?php

namespace Rayo\TenancyExtension;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Rayo\TenancyExtension\Events\CreatedBucket;
use Rayo\TenancyExtension\Events\CreatingBucket;
use Rayo\TenancyExtension\Events\DeletedBucket;
use Rayo\TenancyExtension\Events\DeletingBucket;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class Bucket
{
    public TenantWithDatabase|Model $tenant;
    public Credentials $credentials;
    public ?string $endpoint;
    public ?string $region;
    public string $version = "2006-03-01";
    public bool $pathStyle = false;
    protected ?string $createdBucketName;
    protected ?AwsException $e;

    public function __construct(
        TenantWithDatabase $tenant,
        ?Credentials $credentials = null,
        ?string $endpoint = null,
        ?string $region = null,
        ?bool $pathStyle = null
    ) {
        $this->tenant = $tenant;
        $this->credentials = $credentials ?? new Credentials(
            config('filesystems.disks.s3.key'),
            config('filesystems.disks.s3.secret')
        );
        $this->region = $region ?? config('filesystems.disks.s3.region');
        $this->endpoint = $endpoint ?? config('filesystems.disks.s3.endpoint');
        $pathStyle = $pathStyle ?? config('filesystems.disks.s3.use_path_style_endpoint');
        $this->pathStyle = $pathStyle ?? $this->pathStyle;
    }

    public function createTenantBucket(): self
    {
        $bucketName = config('tenancy.filesystem.suffix_base') . $this->tenant->getTenantKey();

        return $this->createBucket($bucketName, $this->credentials);
    }

    public function deleteTenantBucket(): self
    {
        $bucketName = $this->tenant->tenant_bucket;

        return $bucketName ? $this->deleteBucket($bucketName, $this->credentials) : $this;
    }

    public function createBucket(string $name, Credentials $credentials): self
    {
        event(new CreatingBucket($this->tenant));

        $params = [
            "credentials" => $credentials,
            "endpoint" => $this->endpoint,
            "region" => $this->region,
            "version" => $this->version,
            "use_path_style_endpoint" => $this->pathStyle,
        ];

        $client = new S3Client($params);

        try {
            $client->createBucket(['Bucket' => $name]);

            $this->createdBucketName = $name;

            $this->tenant->bucket = $this->createdBucketName;
            $this->tenant->save();
        } catch (AwsException $e) {
            $this->e = $e;
            Log::error($this->getErrorMessage());
        }

        event(new CreatedBucket($this->tenant));

        return $this;
    }

    public function deleteBucket(string $name, Credentials $credentials): self
    {
        event(new DeletingBucket($this->tenant));

        $params = [
            "credentials" => $credentials,
            "endpoint" => $this->endpoint,
            "region" => $this->region,
            "version" => $this->version,
            "use_path_style_endpoint" => $this->pathStyle,
        ];

        $client = new S3Client($params);

        try {
            $client->deleteBucket(['Bucket' => $name]);
        } catch (AwsException $e) {
            $this->e = $e;
            Log::error($this->getErrorMessage());
        }

        event(new DeletedBucket($this->tenant));

        return $this;
    }

    public function getBucketName(): string|null
    {
        return $this->createdBucketName;
    }

    public function getErrorMessage(): string|null
    {
        return ($this->e) ? "Error: " . $this->e->getAwsErrorMessage() : null;
    }

    public function getErrorBag(): AwsException|null
    {
        return $this->e ? $this->e : null;
    }
}
