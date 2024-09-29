<?php

namespace ShreifElagamy\LaravelServices\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
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
