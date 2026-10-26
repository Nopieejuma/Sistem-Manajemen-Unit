<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        RateLimiter::for('customer-booking-lookup', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request, array $headers): Response {
                    return response()->view('customer.cek-booking', [
                        'notice' => 'Terlalu banyak percobaan. Silakan tunggu satu menit sebelum mencoba kembali.',
                    ], 429, $headers)->header('Cache-Control', 'no-store, private');
                });
        });
    }
}
