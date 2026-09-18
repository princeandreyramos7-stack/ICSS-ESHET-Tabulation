<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Never emit http:// links from a production app served over https.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
    }
}
