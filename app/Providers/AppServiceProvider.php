<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Limit guessing per account + address, not per address alone, so a whole class
        // logging in from the same school network does not lock each other out.
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(8)->by(strtolower((string) $r->input('login')).'|'.$r->ip()));
    }
}
