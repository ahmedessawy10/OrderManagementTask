<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Throwable;

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
        $this->configureLocale();

        $this->app->rebinding('request', function (): void {
            $this->configureLocale();
        });
    }

    public function configureLocale(): void
    {
        $supported = ['en', 'ar'];

        $header = request()->header('Accept-Language', 'en');
        $locale = strtolower(explode(',', (string) $header)[0]);
        $locale = explode('-', $locale)[0];

        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        try {
            $user = auth('api')->user() ?? auth()->user();
            if ($user && ! empty($user->preferred_language) && in_array($user->preferred_language, $supported, true)) {
                $locale = $user->preferred_language;
            }
        } catch (Throwable) {
            // Ignore auth resolution errors during boot/unauthenticated requests
        }

        app()->setLocale($locale);
    }
}
