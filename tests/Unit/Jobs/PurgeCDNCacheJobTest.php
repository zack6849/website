<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\CachePurgeFailureException;
use App\Jobs\Files\PurgeCDNCacheJob;
use App\Services\CDNService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeCDNCacheJobTest extends TestCase
{
    use WithFaker;
    private string $job;

    public function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function callsCDNServicePurgeCache(): void
    {
        $path = $this->faker->slug();
        $endpoint = Str::random(30);
        config(['services.digitalocean.cdn.id' => $endpoint]);
        $this->mock(CDNService::class, function (MockInterface $mock) use ($endpoint, $path) {
            $mock->shouldReceive('purgeCache')
                ->withArgs([$path, $endpoint])
                ->once();
        });
        PurgeCDNCacheJob::dispatchSync($path);
    }

    #[Test]
    public function throwsExceptionOnFailure(): void
    {
        $this->mock(CDNService::class, function (MockInterface $mock) {
            $mock->shouldReceive('purgeCache')->once()->andReturn(false);
        });
        $this->runJob('foo')
            ->assertFailed()
            ->assertFailedWith(CachePurgeFailureException::class);
    }

    private function runJob($path): PurgeCDNCacheJob
    {
        $job = (new PurgeCDNCacheJob($path))->withFakeQueueInteractions();
        $job->handle(resolve(CDNService::class));
        return $job;
    }
}
