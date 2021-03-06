<?php
namespace Newelement\Shoppe;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

use Newelement\Shoppe\Facades\Shoppe as ShoppeFacade;
use Newelement\Shoppe\Http\Middleware\ShoppeCustomerMiddleware;
use Newelement\Shoppe\Composers\ShoppeBreadcrumbComposer;

class ShoppeServiceProvider extends ServiceProvider
{

	public function register()
    {

		$loader = AliasLoader::getInstance();
        $loader->alias('Shoppe', ShoppeFacade::class);
        $this->app->singleton('shoppe', function () {
            return new Shoppe();
        });

        $this->app->singleton('ShoppeAuth', function () {
            return auth();
        });

        // Get connectors
        $this->app->bind('Shipping', function ($app) {
            $class = config('shoppe.shipping_connector','\\Newelement\\Shoppe\\Connectors\\Shipping');
            return new $class();
        });

        $this->app->bind('Taxes', function ($app) {
            $class = config('shoppe.taxes_connector','\\Newelement\\Shoppe\\Connectors\\Taxes');
            return new $class();
        });

        $this->app->bind('Payment', function ($app) {
            $class = config('shoppe.payment_connector','\\Newelement\\Shoppe\\Connectors\\Payment');
            return new $class();
        });

        $this->app->bind('Inventory', function ($app) {
            return new \Newelement\Shoppe\Connectors\InventoryConnector;
        });

		$this->loadHelpers();
		$this->registerConfigs();

		if ($this->app->runningInConsole()) {
            $this->registerPublishableResources();
            $this->registerConsoleCommands();
        }

        \Event::listen('stripe-webhooks::charge.succeeded', \Newelement\Shoppe\Listeners\ChargeSucceeded::class);
        \Event::listen('stripe-webhooks::customer.deleted', \Newelement\Shoppe\Listeners\CustomerDeleted::class);
        \Event::listen('stripe-webhooks::customer.subscription.trial_will_end', \Newelement\Shoppe\Listeners\TrialWillEnd::class);
        \Event::listen('stripe-webhooks::customer.subscription.updated', \Newelement\Shoppe\Listeners\SubscriptionUpdated::class);
        \Event::listen('stripe-webhooks::invoice.payment_succeeded', \Newelement\Shoppe\Listeners\PaymentSucceeded::class);
        \Event::listen('stripe-webhooks::invoice.payment_failed', \Newelement\Shoppe\Listeners\PaymentFailed::class);
	}

	public function boot(Router $router, Dispatcher $event)
	{

		$viewsDirectory = __DIR__.'/../resources/views';
		$publishAssetsDirectory = __DIR__.'/../publishable/assets';

        $this->loadViewsFrom($viewsDirectory, 'shoppe');

        View::composer('shoppe::products', ShoppeBreadcrumbComposer::class);
        View::composer('shoppe::product', ShoppeBreadcrumbComposer::class);
        View::composer('shoppe::cart', ShoppeBreadcrumbComposer::class);
        View::composer('shoppe::checkout', ShoppeBreadcrumbComposer::class);
        View::composer('shoppe::checkout-complete', ShoppeBreadcrumbComposer::class);

		$this->publishes([$viewsDirectory => base_path('resources/views/vendor/shoppe')], 'views');
		$this->publishes([ $publishAssetsDirectory => public_path('vendor/newelement/shoppe') ], 'public');
        $router->aliasMiddleware('shoppe.customer', ShoppeCustomerMiddleware::class);
		$this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));

        // Register routes
        $router->group([
            'namespace' => 'Newelement\Shoppe\Http\Controllers',
            'as' => 'shoppe.',
            'middleware' => ['web']
        ], function ($router) {
            require __DIR__.'/../routes/web.php';
        });

        $router->group([
            'namespace' => 'Newelement\Shoppe\Http\Controllers\Admin',
            'prefix' => 'admin',
            'as' => 'shoppe.',
            'middleware' => ['web', 'admin.user']
        ], function ($router) {
            require __DIR__.'/../routes/admin.php';
        });

        $router->group([
            'namespace' => 'Newelement\Shoppe\Http\Controllers',
            'as' => 'shoppe.',
            'middleware' => ['web', 'shoppe.customer']
        ], function ($router) {
            require __DIR__.'/../routes/customer.php';
        });

        $router->group([
            'namespace' => 'Newelement\Shoppe\Http\Controllers',
            'prefix' => 'api',
            'as' => 'shoppe.',
            'middleware' => 'web'
        ], function ($router) {
            require __DIR__.'/../routes/api.php';
        });

        $router->group([
            'prefix' => 'api',
            'middleware' => 'api'
        ], function ($router) {
            $router->post('stripe-webhooks', '\Spatie\StripeWebhooks\StripeWebhooksController');
        });
	}

	/**
     * Register the publishable files.
     */
    private function registerPublishableResources()
    {
        $publishablePath = dirname(__DIR__).'/publishable';

        $publishable = [
            'config' => [
                "{$publishablePath}/config/shoppe.php" => config_path('shoppe.php'),
            ],
			'seeds' => [
                "{$publishablePath}/database/seeds/" => database_path('seeds'),
            ],
        ];
        foreach ($publishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }

    public function registerConfigs()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/publishable/config/shoppe.php', 'shoppe'
        );
    }

	protected function loadHelpers()
    {
        foreach (glob(__DIR__.'/Helpers/*.php') as $filename) {
            require_once $filename;
        }
    }

	/**
     * Register the commands accessible from the Console.
     */
    private function registerConsoleCommands()
    {
        $this->commands(Commands\InstallCommand::class);
    }

}
