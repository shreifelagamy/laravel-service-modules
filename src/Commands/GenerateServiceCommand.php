<?php

namespace ShreifElagamy\LaravelServiceModules\Commands;

use Laravel\Prompts\Progress;
use Illuminate\Console\Command;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

use function Laravel\Prompts\confirm;
use Illuminate\Filesystem\Filesystem;
use function Laravel\Prompts\progress;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Finder\SplFileInfo;

class GenerateServiceCommand extends Command
{
    private Filesystem $filesystem;

    private Progress $progress;

    private ?string $service_name;
    private ?string $directory;
    private ?array $methods;

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

        $include_exceptions = confirm(
            label: 'Are you planning to have a separate exception for this service ?',
            default: true
        );

        $this->determineServiceMethods();

        // calculating steps count
        $count = count($this->getStubFiles($include_exceptions)) + 1;
        $this->progress = progress(label: "Generating Service Module `{$this->service_name}`", steps: $count);
        $this->createSerivceDirectoryStructure($include_exceptions);
        $this->generateServiceFiles($include_exceptions);

        note("Service Module `{$this->service_name}` generated successfully. Go build something great!", 'warning');
    }

    private function determineServiceMethods(): void
    {
        $methods = text(
            label: "Do you have service methods in mind ?",
            placeholder: "enter names comma separated, or leave empty",
            required: false
        );

        if (!empty($methods)) {
            $methods = explode(',', $methods);
            $methods = array_map(fn(string $method) => str($method)->trim()->camel()->toString(), $methods);

            $confirm = confirm("Are you sure you want to generate methods: " . implode(', ', $methods), default: true);

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

    private function generateServiceFiles(bool $include_exceptions): void
    {
        $files = $this->getStubFiles($include_exceptions);

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

        if ($isInterface && !empty($this->methods)) {
            $methodTemplates = '';

            foreach ($this->methods as $method) {
                $methodTemplates .= $this->methodDefinationTemplate($method);
            }

            $content = str_replace('// Add your methods here', $methodTemplates, $content);
        }

        if ($isRepository && !empty($this->methods)) {
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
    private function getStubFiles(bool $include_exceptions): array
    {
        $files = collect($this->filesystem->files($this->getStubPath()));

        if (!$include_exceptions) {
            $files = $files->filter(fn(SplFileInfo $file) => !str_contains($file->getFilename(), 'exception'));
        }

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

    private function createSerivceDirectoryStructure(bool $include_exceptions): void
    {
        $this->progress->bgGreen('Creating Service Directory Structure');
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Repositories', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Facades', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Providers', 0755, true);

        if ($include_exceptions) {
            $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Exceptions', 0755, true);
        }

        $this->progress->advance();
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
