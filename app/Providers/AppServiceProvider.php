<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        // Daftarkan folder views dan views/komponen agar sintaks <x-komponen.xxx> maupun <x-komponen::xxx> dikenali
        Blade::anonymousComponentPath(resource_path('views'));
        Blade::anonymousComponentPath(resource_path('views/komponen'), 'komponen');
    }
}
