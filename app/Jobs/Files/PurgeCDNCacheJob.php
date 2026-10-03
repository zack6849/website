<?php

declare(strict_types=1);

namespace App\Jobs\Files;

use App\Exceptions\CachePurgeFailureException;
use App\Services\CDNService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeCDNCacheJob implements ShouldQueue
{
    use Queueable;

    public int $backoff = 5;

    public function retryUntil(): CarbonInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public string $path, public ?string $endpointId = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CDNService $service): void
    {
        $endpointId = $this->endpointId ?? config('services.digitalocean.cdn.id');
        $success = $service->purgeCache(
            $this->path,
            $endpointId,
        );
        if (!$success) {
            $this->fail(new CachePurgeFailureException("Failed to purge CDN cache for path {$this->path}"));
        }
    }
}
