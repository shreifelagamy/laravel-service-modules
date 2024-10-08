<?php

namespace ShreifElagamy\LaravelServiceModules\Providers;

use Illuminate\Support\ServiceProvider;
use ShreifElagamy\LaravelServiceModules\Commands\GenerateServiceCommand;

class LaravelServiceModulesProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/laravel-service-modules.php' => config_path('laravel-service-modules.php'),
        ], 'laravel-service-modules-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateServiceCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/laravel-service-modules.php', 'laravel-service-modules'
        );
    }
}