<?php

namespace ShreifElagamy\LaravelServices\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;
    protected function getPackageProviders($app)
    {
        return [
            \ShreifElagamy\LaravelServices\Providers\LaravelServicesProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        // Clean up generated files after each test
        File::deleteDirectory(app_path('Services'));
        parent::tearDown();
    }
}
