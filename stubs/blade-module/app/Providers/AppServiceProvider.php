<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\View\Components\AppLayout;
use Modules\Auth\View\Components\GuestLayout;

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
        Blade::component('auth::guest-layout', GuestLayout::class);
        Blade::component('auth::app-layout', AppLayout::class);
    }
}
