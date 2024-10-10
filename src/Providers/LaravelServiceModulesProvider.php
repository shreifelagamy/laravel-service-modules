<?php

namespace ShreifElagamy\LaravelServiceModules\Providers;

use Illuminate\Support\ServiceProvider;
use ShreifElagamy\LaravelServiceModules\Commands\GenerateServiceCommand;

class LaravelServiceModulesProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateServiceCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../../config/laravel-service-modules.php' => config_path('laravel-service-modules.php'),
        ], 'laravel-service-modules-config');

        $this->registerGeneratedServiceProviders();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/laravel-service-modules.php',
            'laravel-service-modules'
        );
    }

    private function registerGeneratedServiceProviders()
    {
        $directoryName = config('laravel-service-modules.directory', 'Services');
        $servicesPath = app_path($directoryName);
        if (!is_dir($servicesPath)) {
            return;
        }

        $directories = glob($servicesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $serviceName = basename($directory);
            $providerClass = app()->getNamespace() . "{$directoryName}\\{$serviceName}\\Providers\\{$serviceName}Provider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}