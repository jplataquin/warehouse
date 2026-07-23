<?php

namespace App\Providers;

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

        View::composer('logger.partials.sidebar', function ($view) {
            if (Auth::check() && (Auth::user()->role === 'logger' || Auth::user()->role === 'viewer')) {
                $view->with('warehouses', Auth::user()->warehouses()->active()->get());
            }
        });
    }
}
