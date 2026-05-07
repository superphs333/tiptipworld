<?php

namespace App\Providers;

use App\Models\Tip;
use App\Policies\TipPolicy;
use App\Services\SearchKeywordService;
use App\View\Composers\AuthSocialProvidersComposer;
use App\View\Composers\DeleteUserFormComposer;
use App\View\Composers\ProfileSocialConnectionsComposer;
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

        View::composer('auth.partials.social-providers', AuthSocialProvidersComposer::class);
        View::composer('profile.partials.social-connections', ProfileSocialConnectionsComposer::class);
        View::composer('profile.partials.delete-user-form', DeleteUserFormComposer::class);
    }
}
