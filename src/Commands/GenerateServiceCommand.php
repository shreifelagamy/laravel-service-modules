<?php

namespace ShreifElagamy\LaravelServiceModules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Prompts\Progress;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\text;

class GenerateServiceCommand extends Command
{
    private Filesystem $filesystem;

    private Progress $progress;

    private ?string $service_name;

    private ?string $directory;

    private ?array $methods;

    private bool $include_exceptions = true;

    private ?array $dtos;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:generate {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new service module';

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->directory = str(config('laravel-service-modules.directory', 'Services'))->ucfirst();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->prepareTheServiceName() == false) {
            return;
        }

        $this->include_exceptions = confirm(
            label: 'Are you planning to have a separate exception for this service ?',
            default: true
        );

        $this->determineDTOs();
        $this->determineServiceMethods();

        // calculating steps count
        $count = count($this->getStubFiles()) + 1;
        if (! empty($this->dtos)) {
            $count += count($this->dtos);
        }
        $this->progress = progress(label: "Generating Service Module `{$this->service_name}`", steps: $count);
        $this->createSerivceDirectoryStructure();
        $this->generateServiceFiles();

        if (! empty($this->dtos)) {
            $this->generateDTOs();
        }

        note("Service Module `{$this->service_name}` generated successfully. Go build something great!", 'warning');
    }

    private function determineDTOs(): void
    {
        $dtos = text(
            label: 'Do you want to include DTOs for this service?',
            placeholder: 'Enter names comma separated, or leave empty',
            required: false
        );

        if (! empty($dtos)) {
            $dtos = explode(',', $dtos);
            $dtos = array_map(fn(string $dto) => str($dto)->trim()->studly()->toString(), $dtos);

            $confirm = confirm('Are you sure you want to generate DTOs: ' . implode(', ', $dtos), default: true);

            if ($confirm) {
                $this->dtos = $dtos;
            } else {
                $this->determineDTOs();
            }
        }
    }

    private function determineServiceMethods(): void
    {
        $methods = text(
            label: 'Do you have service methods in mind ?',
            placeholder: 'Enter names comma separated, or leave empty',
            required: false
        );

        if (! empty($methods)) {
            $methods = explode(',', $methods);
            $methods = array_map(fn(string $method) => str($method)->trim()->camel()->toString(), $methods);

            $confirm = confirm('Are you sure you want to generate methods: ' . implode(', ', $methods), default: true);

            if ($confirm) {
                $this->methods = $methods;
            } else {
                $this->determineServiceMethods();
            }
        }
    }

    /**
     **
     * Map the stub variables present in stub to its value
     */
    public function getStubVariables(): array
    {
        return [
            '$REPO_NAMESPACE$' => app()->getNamespace() . "{$this->directory}\\{$this->service_name}\\Repositories",
            '$PROVIDER_NAMESPACE$' => app()->getNamespace() . "{$this->directory}\\{$this->service_name}\\Providers",
            '$FACADE_NAMESPACE$' => app()->getNamespace() . "{$this->directory}\\{$this->service_name}\\Facades",
            '$EXCEPTION_NAMESPACE$' => app()->getNamespace() . "{$this->directory}\\{$this->service_name}\\Exceptions",
            '$SERVICE_NAME$' => $this->service_name,
        ];
    }

    private function getStubPath()
    {
        return __DIR__ . '/../stubs';
    }

    private function getServicesPath(): string
    {
        return app_path($this->directory);
    }

    private function generateServiceFiles(): void
    {
        $files = $this->getStubFiles();

        foreach ($files as $file) {
            $this->generateServiceFile($file);
            $this->progress->advance();
        }

        $this->progress->finish();
    }

    private function generateServiceFile(SplFileInfo $file): void
    {
        $content = $file->getContents();
        $isInterface = str_contains($file->getFilename(), 'interface');
        $isRepository = str_contains($file->getFilename(), 'repository');

        // Replace content
        foreach ($this->getStubVariables() as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        if ($isInterface && ! empty($this->methods)) {
            $methodTemplates = '';

            foreach ($this->methods as $method) {
                $methodTemplates .= $this->methodDefinationTemplate($method);
            }

            $content = str_replace('// Add your methods here', $methodTemplates, $content);
        }

        if ($isRepository && ! empty($this->methods)) {
            $methodsContent = '';
            foreach ($this->methods as $method) {
                $methodsContent .= $this->methodTemplate($method);
            }

            $content = str_replace('// Add your methods here', $methodsContent, $content);
        }

        $file_path = $this->guessFilePath($file);
        $this->filesystem->put($this->getServicesPath() . "/{$this->service_name}/" . $file_path, $content);
    }

    private function guessFilePath(SplFileInfo $file): string
    {
        $info = explode('.', $file->getFilename());
        $path = str($this->service_name)->prepend("$info[0]/");

        if ($info[1] !== 'stub') {
            $append = ucfirst($info[1]);
            $path = $path->append("{$append}");
        }

        $path = $path->append('.php');

        return $path;
    }

    /**
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    private function getStubFiles(): array
    {
        $files = collect($this->filesystem->files($this->getStubPath()));

        $files = $files->filter(function (SplFileInfo $file) {
            // Exclude exception stubs if not needed
            if (!$this->include_exceptions && str_contains($file->getFilename(), 'exception')) {
                return false;
            }
            // Exclude DTO stubs
            if (str_contains($file->getFilename(), 'dto')) {
                return false;
            }
            return true;
        });

        return $files->toArray();
    }

    private function prepareTheServiceName(): bool
    {
        $this->service_name = $this->argument('name');

        if (empty($this->service_name)) {
            $this->service_name = text(
                label: 'Enter the service name',
                placeholder: 'ExampleService',
                required: true,
                validate: fn(string $value) => match (true) {
                    empty($value) => 'Service name is required',
                    $this->filesystem->exists($this->getServicesPath() . '/' . $value) => 'Service already exists',
                    default => null,
                }
            );
        } else {
            if ($this->filesystem->exists($this->getServicesPath() . '/' . $this->service_name)) {
                $this->error("Service '{$this->service_name}' already exists.");

                return false;
            }
        }

        $this->service_name = str($this->service_name)->trim()->studly();

        return true;
    }

    private function createSerivceDirectoryStructure(): void
    {
        $this->progress->bgGreen('Creating Service Directory Structure');
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Repositories', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Facades', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Providers', 0755, true);

        if ($this->include_exceptions) {
            $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Exceptions', 0755, true);
        }

        if (! empty($this->dtos)) {
            $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/DTOs', 0755, true);
        }

        $this->progress->advance();
    }

    private function generateDTOs(): void
    {
        foreach ($this->dtos as $dto) {
            $content = $this->filesystem->get($this->getStubPath() . '/dto.stub');
            $content = str_replace('$DTO_NAMESPACE$', app()->getNamespace() . "{$this->directory}\\{$this->service_name}\\DTOs", $content);
            $content = str_replace('$DTO_NAME$', $dto, $content);

            $file_path = $this->getServicesPath() . "/{$this->service_name}/DTOs/{$dto}.php";
            $this->filesystem->put($file_path, $content);

            $this->progress->advance();
        }
    }

    private function methodTemplate(string $methodName): string
    {
        return "
        public function {$methodName}(): void
        {
            //
        }
        \n
        ";
    }

    private function methodDefinationTemplate(string $methodName): string
    {
        return "
        public function {$methodName}(): void;
        \n
        ";
    }
}
