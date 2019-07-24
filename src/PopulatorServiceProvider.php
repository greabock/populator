<?php

namespace Greabock\Populator;

use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Illuminate\Support\ServiceProvider;

class PopulatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(KeyGeneratorInterface::class, UuidGenerator::class);
        $this->app->singleton(UnitOfWork::class);
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(Populator::class);
    }
}
