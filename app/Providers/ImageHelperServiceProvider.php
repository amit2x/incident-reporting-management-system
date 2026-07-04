<?php

namespace App\Providers;

use App\Helpers\ImageHelper;
use Illuminate\Support\ServiceProvider;

class ImageHelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageHelper::class, function ($app) {
            return new ImageHelper;
        });

        // Register as a facade alias (optional)
        $this->app->alias(ImageHelper::class, 'imagehelper');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration if needed (optional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/image-helper.php' => config_path('image-helper.php'),
            ], 'image-helper-config');
        }
    }
}
