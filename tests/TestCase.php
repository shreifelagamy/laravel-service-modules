<?php

namespace ShreifElagamy\LaravelServiceModules\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;
    protected function getPackageProviders($app)
    {
        return [
            \ShreifElagamy\LaravelServiceModules\Providers\LaravelServiceModulesProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        $directory = str(config('laravel-service-modules.directory', 'Services'))->ucfirst();

        // Clean up generated files after each test
        File::deleteDirectory(app_path($directory));

        parent::tearDown();
    }
}
