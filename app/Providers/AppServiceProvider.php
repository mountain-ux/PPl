<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('local')) {
            $marker = storage_path('framework/views/.tamuin_ui_v5');
            if (!file_exists($marker)) {
                try {
                    Artisan::call('view:clear');
                    @file_put_contents($marker, date('c'));
                } catch (\Throwable $e) {
                    // Abaikan jika direktori belum siap
                }
            }
        }
    }
}
