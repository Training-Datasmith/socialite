<?php

declare (strict_types=1);
namespace Laravel\Socialite;

use Illuminate\Contracts\Support\Deferrable_Provider;
use Illuminate\Support\Service_Provider;
use Laravel\Socialite\Contracts\Factory;
class Socialite_Service_Provider extends Service_Provider implements Deferrable_Provider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(Factory::class, function ($app): \Laravel\Socialite\Socialite_Manager {
            return new Socialite_Manager($app);
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Factory::class];
    }
}