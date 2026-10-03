<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
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
        config(['app.trusted_proxies' => null]);

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
        config(['app.trusted_proxies' => $proxies]);

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
}
