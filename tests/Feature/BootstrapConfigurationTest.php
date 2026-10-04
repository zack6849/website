<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\File;
use App\Models\User;
use App\Policies\FilePolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BootstrapConfigurationTest extends TestCase
{
    #[Test]
    public function usesFrameworkKernelsAndExceptionHandler(): void
    {
        $this->assertSame(
            \Illuminate\Foundation\Http\Kernel::class,
            $this->app->make(HttpKernel::class)::class,
        );
        $this->assertSame(
            \Illuminate\Foundation\Console\Kernel::class,
            $this->app->make(ConsoleKernel::class)::class,
        );
        $this->assertSame(
            \Illuminate\Foundation\Exceptions\Handler::class,
            $this->app->make(ExceptionHandler::class)::class,
        );
    }

    #[Test]
    public function registersMiddlewarePoliciesAndEmailVerification(): void
    {
        $kernel = $this->app->make(HttpKernel::class);

        $this->assertSame(EnsureUserIsAdmin::class, $kernel->getMiddlewareAliases()['admin']);
        $this->assertContains(PreventRequestForgery::class, $kernel->getMiddlewareGroups()['web']);
        $this->assertSame(
            ['throttle:60,1', \Illuminate\Routing\Middleware\SubstituteBindings::class],
            $kernel->getMiddlewareGroups()['api'],
        );
        $this->assertInstanceOf(FilePolicy::class, Gate::getPolicyFor(File::class));
        $this->assertSame(
            [SendEmailVerificationNotification::class],
            Event::getRawListeners()[Registered::class],
        );
    }

    #[Test]
    public function registersDailyImportScheduleAndCommand(): void
    {
        $events = array_values(array_filter(
            $this->app->make(Schedule::class)->events(),
            fn ($event) => str_contains($event->command ?? '', 'logbook:import'),
        ));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events[0]->expression);
        $this->artisan('list', ['--raw' => true])->expectsOutputToContain('logbook:import')->assertSuccessful();
    }

    #[Test]
    public function servesHealthAndApiRoutes(): void
    {
        $this->get('/up')->assertOk();
        $this->getJson('/api/radio/bands')->assertOk();
        $this->postJson('/api/files')->assertUnauthorized();
    }

    #[Test]
    public function preservesGuestAndAuthenticatedRedirects(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/');
    }

    #[Test]
    public function webMiddlewareStartsSessionsAndProtectsPostRequests(): void
    {
        Route::middleware('web')->post('/_test/csrf', fn () => ['session' => session('value')]);

        // Exercise request-forgery checks normally bypassed in the testing environment.
        $this->app['env'] = 'local';

        try {
            $this->post('/_test/csrf')->assertStatus(419);
            $this->withSession(['_token' => 'test-token', 'value' => 'persisted'])
                ->post('/_test/csrf', ['_token' => 'test-token'])
                ->assertOk()
                ->assertExactJson(['session' => 'persisted']);
            $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
                ->post('/_test/csrf')
                ->assertOk();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    #[Test]
    public function preservesApiRateLimiting(): void
    {
        Route::middleware('api')->get('/_test/throttled', fn () => ['ok' => true]);

        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/_test/throttled')->assertOk();
        }

        $this->getJson('/_test/throttled')->assertTooManyRequests();
    }
}
