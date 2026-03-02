<?php

namespace App\Providers;

use App\Services\SearchKeywordService;
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
        View::composer('components.search-modal', function ($view) {
            $searchKeywordService = app(SearchKeywordService::class);

            $view->with([
                'rankings' => $searchKeywordService->top(5),
                'date' => now()->format('Y.m.d'),
            ]);
        });
    }
}
