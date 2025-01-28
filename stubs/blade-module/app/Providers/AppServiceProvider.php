<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\{{moduleName}}\View\Components\AppLayout;
use Modules\{{moduleName}}\View\Components\GuestLayout;

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
        Blade::component('{{moduleNameLower}}::guest-layout', GuestLayout::class);
        Blade::component('{{moduleNameLower}}::app-layout', AppLayout::class);
    }
}
