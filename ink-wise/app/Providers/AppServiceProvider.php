<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Support\ImageResolver;

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
        // Blade helper to resolve image URLs: @imageUrl($path)
        Blade::directive('imageUrl', function ($expression) {
            return "<?php echo \App\\Support\\ImageResolver::url({$expression}); ?>";
        });
    }
}
