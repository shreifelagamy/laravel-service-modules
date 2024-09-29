<?php

namespace ShreifElagamy\LaravelServices\Providers;

use Illuminate\Support\ServiceProvider;
use ShreifElagamy\LaravelServices\Commands\GenerateServiceCommand;

class LaravelServicesProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateServiceCommand::class,
            ]);
        }
    }
}