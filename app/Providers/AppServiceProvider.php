<?php

namespace App\Providers;

use App\Models\Ledger;
use App\Observers\LedgerObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
        Paginator::useBootstrapFive();

        Ledger::observe(LedgerObserver::class);

        View::composer('logger.partials.sidebar', function ($view) {
            if (Auth::check() && (Auth::user()->role === 'logger' || Auth::user()->role === 'viewer')) {
                $view->with('warehouses', Auth::user()->warehouses()->active()->whereNull('parent_id')->get());
            }
        });
    }
}
