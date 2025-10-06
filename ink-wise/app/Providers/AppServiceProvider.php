<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\SiteSetting;
use App\Support\ImageResolver;
use App\Support\MessageMetrics;

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

        View::composer('layouts.admin', function ($view) {
            $count = 0;

            if (Auth::check()) {
                $count = MessageMetrics::adminUnreadCount();
            }

            $view->with('adminUnreadMessageCount', $count);
        });

        View::composer('layouts.staffapp', function ($view) {
            $count = 0;

            if (Auth::check() || Auth::guard('staff')->check()) {
                $count = MessageMetrics::adminUnreadCount();
            }

            $view->with('staffUnreadMessageCount', $count);
        });

        View::composer([
            'customer.partials.contact',
            'customer.partials.about',
        ], function ($view) {
            $view->with('siteSettings', SiteSetting::current());
        });
    }
}
