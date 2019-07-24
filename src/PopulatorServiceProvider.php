<?php

namespace Greabock\Populator;

use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Illuminate\Support\ServiceProvider;

class PopulatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/populator.php', 'populator');

        $this->app->singleton(KeyGeneratorInterface::class, $this->app['config']['populator.key_generator']);
        $this->app->singleton(UnitOfWork::class);
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(Populator::class);
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/populator.php' => config_path('populator.php')]);
    }
}
