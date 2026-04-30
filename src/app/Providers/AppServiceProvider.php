<?php

namespace App\Providers;

use App\Models\Tip;
use App\Policies\TipPolicy;
use App\Services\SearchKeywordService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        View::composer('components.search-modal', function ($view) {
            $searchKeywordService = app(SearchKeywordService::class);

            $view->with([
                'rankings' => $searchKeywordService->top(5),
                'date' => now()->format('Y.m.d'),
            ]);
        });
    }
}
