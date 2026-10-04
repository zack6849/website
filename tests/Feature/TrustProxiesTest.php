<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TrustProxies::flushState();
        config(['trustedproxy.proxies' => null]);

        Route::get('/_test/client-ip', fn (Request $request) => [
            'ip' => $request->ip(),
        ]);
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function refreshDatabase(): void
    {
        // These requests do not access the database.
    }

    #[Test]
    #[DataProvider('proxyConfigurations')]
    public function resolvesClientIpUsingConfiguredTrustedProxies(
        array|string|null $proxies,
        string $remoteAddress,
        ?string $forwardedFor,
        string $expectedIp,
    ): void {
        config(['trustedproxy.proxies' => $proxies]);

        $server = ['REMOTE_ADDR' => $remoteAddress];

        if ($forwardedFor !== null) {
            $server['HTTP_X_FORWARDED_FOR'] = $forwardedFor;
        }

        $this->withServerVariables($server)
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertExactJson(['ip' => $expectedIp]);
    }

    public static function proxyConfigurations(): array
    {
        return [
            'trusted proxy uses forwarded client IP' => [
                '10.0.0.10', '10.0.0.10', '203.0.113.25', '203.0.113.25',
            ],
            'untrusted source cannot spoof client IP' => [
                '10.0.0.10', '198.51.100.20', '203.0.113.25', '198.51.100.20',
            ],
            'no configured proxies ignores forwarded header' => [
                null, '10.0.0.10', '203.0.113.25', '10.0.0.10',
            ],
            'trusted CIDR accepts proxy in range' => [
                '10.0.0.0/24', '10.0.0.10', '203.0.113.25', '203.0.113.25',
            ],
            'trusted CIDR rejects source outside range' => [
                '10.0.0.0/24', '10.0.1.10', '203.0.113.25', '10.0.1.10',
            ],
            'comma separated proxies handle a trusted chain' => [
                '10.0.0.10, 10.0.0.11', '10.0.0.10', '203.0.113.25, 10.0.0.11', '203.0.113.25',
            ],
            'array of proxies handles a trusted chain' => [
                ['10.0.0.10', '10.0.0.11'], '10.0.0.10', '203.0.113.25, 10.0.0.11', '203.0.113.25',
            ],
            'wildcard trusts immediate proxy' => [
                '*', '10.0.0.10', '203.0.113.25', '203.0.113.25',
            ],
            'missing forwarded header retains remote address' => [
                '10.0.0.10', '10.0.0.10', null, '10.0.0.10',
            ],
            'untrusted hop stops traversal of forwarded chain' => [
                '10.0.0.10', '10.0.0.10', '203.0.113.25, 198.51.100.20', '198.51.100.20',
            ],
        ];
    }

    #[Test]
    public function ignoresSpoofedForwardedHostAndSchemeWithoutTrustedProxies(): void
    {
        Route::get('/_test/request-origin', fn (Request $request) => [
            'ip' => $request->ip(),
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.20',
            'HTTP_HOST' => 'zcraig.me',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_X_FORWARDED_FOR' => '203.0.113.25',
            'HTTP_X_FORWARDED_HOST' => 'attacker.invalid',
            'HTTP_X_FORWARDED_PROTO' => 'http',
        ])->getJson('https://zcraig.me/_test/request-origin')
            ->assertOk()
            ->assertExactJson([
                'ip' => '198.51.100.20',
                'host' => 'zcraig.me',
                'scheme' => 'https',
            ]);
    }
}
