<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\File;
use App\Models\User;
use App\Policies\FilePolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Mockery;
use Sentry\Laravel\ServiceProvider as SentryServiceProvider;
use Sentry\Laravel\Tracing\ServiceProvider as SentryTracingServiceProvider;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->environment('production')) {
            $this->app->register(SentryServiceProvider::class);
            $this->app->register(SentryTracingServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(File::class, FilePolicy::class);

        Livewire::addPersistentMiddleware(EnsureUserIsAdmin::class);

        $this->app->bind(Client::class, function () {
            // The Twilio SDK's CurlClient defaults to a 60s timeout with no
            // override anywhere else in the app, so an uncached lookup (which
            // chains through Twilio's Lookup API + the Ekata reverse-phone
            // add-on) can silently hang a web request for up to a minute if
            // either upstream is slow. Fail faster instead.
            $httpClient = new CurlClient([
                CURLOPT_TIMEOUT => config('twilio.lookup_timeout_seconds', 20),
                CURLOPT_CONNECTTIMEOUT => config('twilio.lookup_connect_timeout_seconds', 5),
            ]);

            return new Client(config('twilio.sid'), config('twilio.token'), null, null, $httpClient);
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('access-admin', function (User $user) {
            return $user->isAdmin();
        });
    }
}
