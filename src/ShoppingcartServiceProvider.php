<?php

namespace Gloudemans\Shoppingcart;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

class ShoppingcartServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->bind('cart', Cart::class);

        $this->mergeConfigFrom(__DIR__.'/Config/cart.php', 'cart');

        $this->app->make(Dispatcher::class)->listen(Logout::class, function () {
            if (config('cart.destroy_on_logout')) {
                $this->app->make(SessionManager::class)->forget('cart');
            }
        });
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $this->publishes([__DIR__.'/Config/cart.php' => config_path('cart.php')], 'config');

        $this->publishes([
            realpath(__DIR__.'/Database/migrations') => $this->app->databasePath().'/migrations',
        ], 'migrations');
    }
}
