<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCDNCacheConfigurationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CDNService
{
    private const string BASE_URL = 'https://api.digitalocean.com/';
    private Factory|PendingRequest $client;

    public function __construct(?string $token = null)
    {
        if ($token == null) {
            $token = config('services.digitalocean.key');
        }
        $this->client = Http::baseUrl(static::BASE_URL)
            ->timeout(10)
            ->connectTimeout(3)
            ->withHeaders([
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ]);
    }

    /**
     * @throws InvalidCDNCacheConfigurationException
     * @throws ConnectionException
     */
    public function purgeCache(string $path, ?string $endpointId = null): bool
    {
        if ($endpointId == null) {
            $endpointId = config('services.digitalocean.cdn.id');
            if($endpointId == null){
                throw new InvalidCDNCacheConfigurationException("CDN endpoint ID is not configured");
            }
        }
        return $this->client->delete("/v2/cdn/endpoints/$endpointId/cache", [
            'files' => [$path]
        ])->successful();
    }
}
