<?php

namespace App\Providers;

use App\Models\Tip;
use App\Policies\TipPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Tip::class, TipPolicy::class);
        Vite::prefetch(concurrency: 3);
    }
}
