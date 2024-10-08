<?php

namespace ShreifElagamy\LaravelServiceModules\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use ShreifElagamy\LaravelServiceModules\Commands\GenerateServiceCommand;

class CreateServiceTest extends TestCase
{
    #[Test]
    public function it_can_generate_service_files()
    {
        $serviceName = 'TestService';

        $this->artisan(GenerateServiceCommand::class)
            ->expectsQuestion('Enter the service name', $serviceName)
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        $this->assertFileExists(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
    }

    #[Test]
    public function it_generates_correct_content_in_service_files()
    {
        $serviceName = 'TestService';

        $this->artisan(GenerateServiceCommand::class)
            ->expectsQuestion('Enter the service name', $serviceName)
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        $providerContent = File::get(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $interfaceContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $repositoryContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $facadeContent = File::get(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));

        $this->assertStringContainsString("class {$serviceName}Provider extends ServiceProvider", $providerContent);
        $this->assertStringContainsString("interface {$serviceName}Interface", $interfaceContent);
        $this->assertStringContainsString("class {$serviceName}Repository implements {$serviceName}Interface", $repositoryContent);
        $this->assertStringContainsString("class {$serviceName} extends Facade", $facadeContent);
        $this->assertStringContainsString('protected static function getFacadeAccessor()', $facadeContent);
        $this->assertStringContainsString("return {$serviceName}Interface::class;", $facadeContent);

        $exceptionContent = File::get(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
        $this->assertStringContainsString("class {$serviceName}Exception extends Exception", $exceptionContent);
    }

    #[Test]
    public function it_can_generate_service_files_with_optional_name_argument()
    {
        $serviceName = 'OptionalNameService';

        $this->artisan(GenerateServiceCommand::class, ['name' => $serviceName])
            ->doesntExpectOutput('Enter the service name')
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        $this->assertFileExists(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));

        // Check content of generated files
        $providerContent = File::get(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $interfaceContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $repositoryContent = File::get(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $facadeContent = File::get(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));

        $this->assertStringContainsString("class {$serviceName}Provider extends ServiceProvider", $providerContent);
        $this->assertStringContainsString("interface {$serviceName}Interface", $interfaceContent);
        $this->assertStringContainsString("class {$serviceName}Repository implements {$serviceName}Interface", $repositoryContent);
        $this->assertStringContainsString("class {$serviceName} extends Facade", $facadeContent);

        $exceptionContent = File::get(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
        $this->assertStringContainsString("class {$serviceName}Exception extends Exception", $exceptionContent);
    }

    #[Test]
    public function it_throws_error_when_service_already_exists_with_optional_name_argument()
    {
        $serviceName = 'ExistingService';

        // First, create the service
        $this->artisan(GenerateServiceCommand::class, ['name' => $serviceName])
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        // Try to create the same service again
        $this->artisan(GenerateServiceCommand::class, ['name' => $serviceName])
            ->expectsOutput("Service '{$serviceName}' already exists.")
            ->assertExitCode(0);
    }

    #[Test]
    public function it_can_generate_service_files_without_exceptions()
    {
        $serviceName = 'TestServiceWithoutExceptions';

        $this->artisan(GenerateServiceCommand::class)
            ->expectsQuestion('Enter the service name', $serviceName)
            ->expectsQuestion('Do you want to include exceptions in the service?', false)
            ->assertExitCode(0);

        $this->assertFileExists(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));
        $this->assertFileDoesNotExist(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
    }

    #[Test]
    public function it_can_generate_service_files_with_exceptions()
    {
        $serviceName = 'TestServiceWithExceptions';

        $this->artisan(GenerateServiceCommand::class)
            ->expectsQuestion('Enter the service name', $serviceName)
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        $this->assertFileExists(app_path("Services/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Facades/{$serviceName}.php"));
        $this->assertFileExists(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));

        $exceptionContent = File::get(app_path("Services/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
        $this->assertStringContainsString("class {$serviceName}Exception extends Exception", $exceptionContent);
    }

    #[Test]
    public function it_can_generate_service_files_with_custom_directory()
    {
        $serviceName = 'CustomDirectoryService';
        Config::set('laravel-services.directory', 'CustomServices');

        $this->artisan(GenerateServiceCommand::class)
            ->expectsQuestion('Enter the service name', $serviceName)
            ->expectsQuestion('Do you want to include exceptions in the service?', true)
            ->assertExitCode(0);

        $this->assertFileExists(app_path("CustomServices/{$serviceName}/Providers/{$serviceName}Provider.php"));
        $this->assertFileExists(app_path("CustomServices/{$serviceName}/Repositories/{$serviceName}Interface.php"));
        $this->assertFileExists(app_path("CustomServices/{$serviceName}/Repositories/{$serviceName}Repository.php"));
        $this->assertFileExists(app_path("CustomServices/{$serviceName}/Facades/{$serviceName}.php"));
        $this->assertFileExists(app_path("CustomServices/{$serviceName}/Exceptions/{$serviceName}Exception.php"));
    }
}