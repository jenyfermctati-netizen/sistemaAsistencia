<?php

namespace App\Providers;

use App\Models\Marcacion;
use App\Observers\MarcacionObserver;
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
        Marcacion::observe(MarcacionObserver::class);
    }
}
