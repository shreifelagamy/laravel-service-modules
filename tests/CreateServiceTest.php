<?php

namespace ShreifElagamy\LaravelServices\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\Test;
use ShreifElagamy\LaravelServices\Tests\TestCase;

class CreateServiceTest extends TestCase
{
    use WithWorkbench;

    // protected function setUp(): void
    // {
    //     parent::setUp();
    //     // Additional setup if needed
    // }

    // protected function tearDown(): void
    // {
    //     // Clean up generated files after each test
    //     File::deleteDirectory(app_path('Services'));
    //     parent::tearDown();
    // }

    #[Test]
    public function it_can_generate_service_files()
    {
        $serviceName = 'TestService';

        Artisan::call('make:service', ['name' => $serviceName]);

        $this->assertFileExists(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));
    }

    #[Test]
    public function it_generates_correct_content_in_service_files()
    {
        $serviceName = 'TestService';

        Artisan::call('make:service', ['name' => $serviceName]);

        $providerContent = File::get(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $interfaceContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $repositoryContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $facadeContent = File::get(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));

        $this->assertStringContainsString("class {$serviceName}Provider extends ServiceProvider", $providerContent);
        $this->assertStringContainsString("interface {$serviceName}Interface", $interfaceContent);
        $this->assertStringContainsString("class {$serviceName}Repository implements {$serviceName}Interface", $repositoryContent);
        $this->assertStringContainsString("class {$serviceName} extends Facade", $facadeContent);
        $this->assertStringContainsString("protected static function getFacadeAccessor()", $facadeContent);
        $this->assertStringContainsString("return '{$serviceName}';", $facadeContent);
    }

    #[Test]
    public function it_throws_exception_for_invalid_service_name()
    {
        $this->expectException(\InvalidArgumentException::class);

        Artisan::call('make:service', ['name' => '123InvalidName']);
    }
}

