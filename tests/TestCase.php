<?php

namespace ShreifElagamy\LaravelServices\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \ShreifElagamy\LaravelServices\Providers\LaravelServicesProvider::class,
        ];
    }
}
