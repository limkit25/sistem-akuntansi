<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
    //    $ngrokUrl = 'https://d41a84364335.ngrok-free.app';

    //     // Paksa semua link asset() untuk menggunakan URL ini
    //     if (app()->environment('local')) {
    //         URL::forceRootUrl($ngrokUrl);
    //         URL::forceScheme('https');
    //     }
    }
}
