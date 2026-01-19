<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\SiteSetting;
use App\Support\ImageResolver;
use App\Support\MessageMetrics;
use App\Models\Payment;
use App\Observers\PaymentObserver;

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
        $this->boostPayloadLimits();

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

        Payment::observe(PaymentObserver::class);
    }

    private function boostPayloadLimits(): void
    {
        // Increase PHP limits for heavy design JSON payloads
        $limits = [
            'post_max_size' => '64M',
            'upload_max_filesize' => '64M',
            'memory_limit' => '512M',
        ];

        foreach ($limits as $key => $value) {
            try {
                ini_set($key, $value);
            } catch (\Throwable $e) {
                Log::debug('Unable to apply ini limit', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        try {
            $connection = DB::connection();
            if ($connection->getDriverName() === 'mysql') {
                $connection->statement('SET SESSION max_allowed_packet=67108864');
            }
        } catch (\Throwable $e) {
            Log::debug('Unable to raise max_allowed_packet', ['error' => $e->getMessage()]);
        }
    }
}
