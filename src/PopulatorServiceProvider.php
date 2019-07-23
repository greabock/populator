<?php

namespace Greabock\Populator;

use Illuminate\Support\ServiceProvider;

class PopulatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(UnitOfWork::class);
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(Populator::class);
    }
}
