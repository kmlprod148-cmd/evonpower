<?php

namespace App\Providers;

use App\Services\MoneyService;
use App\Helpers\MoneyHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class MoneyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MoneyService::class, function ($app) {
            return new MoneyService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives for money formatting
     */
    protected function registerBladeDirectives(): void
    {
        // @money directive
        Blade::directive('money', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::format($expression); ?>";
        });

        // @moneyWithSymbol directive
        Blade::directive('moneyWithSymbol', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatWithSymbol($expression); ?>";
        });

        // @moneyWithColor directive
        Blade::directive('moneyWithColor', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatWithColor($expression); ?>";
        });

        // @moneyWithBadge directive
        Blade::directive('moneyWithBadge', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatWithBadge($expression); ?>";
        });

        // @moneyForTable directive
        Blade::directive('moneyForTable', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatForTable($expression); ?>";
        });

        // @moneyForCard directive
        Blade::directive('moneyForCard', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatForCard($expression); ?>";
        });

        // @moneySmall directive
        Blade::directive('moneySmall', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatSmall($expression); ?>";
        });

        // @moneyLarge directive
        Blade::directive('moneyLarge', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::formatLarge($expression); ?>";
        });

        // @currencySymbol directive
        Blade::directive('currencySymbol', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::getCurrencySymbol($expression); ?>";
        });

        // @currencyName directive
        Blade::directive('currencyName', function ($expression) {
            return "<?php echo App\Helpers\MoneyHelper::getCurrencyName($expression); ?>";
        });
    }
}
