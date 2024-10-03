<?php

namespace ShreifElagamy\LaravelServices\Commands;

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

    private string|null $service_name;

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
    protected $description = 'Generate a new service';

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     */
    public function handle()
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
                return;
            }
        }

        $this->prepareTheServiceName();

        $include_exceptions = confirm(
            label: 'Do you want to include exceptions in the service?',
            default: true
        );

        // calculating steps count
        $count = count($this->getStubFiles($include_exceptions)) + 1;
        $this->progress = progress(label: "Generating Service `{$this->service_name}`", steps: $count);
        $this->createSerivceDirectoryStructure($include_exceptions);
        $this->generateServiceFiles($include_exceptions);

        note("Service `{$this->service_name}` generated successfully", 'warning');
    }

    /**
     **
     * Map the stub variables present in stub to its value
     */
    public function getStubVariables(): array
    {
        return [
            '$REPO_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Repositories",
            '$PROVIDER_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Providers",
            '$FACADE_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Facades",
            '$EXCEPTION_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Exceptions",
            '$SERVICE_NAME$' => $this->service_name,
        ];
    }

    private function getStubPath()
    {
        return __DIR__ . '/../stubs';
    }

    private function getServicesPath()
    {
        return app_path('Services');
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

        // Replace content
        foreach ($this->getStubVariables() as $key => $value) {
            $content = str_replace($key, $value, $content);
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
            $files = $files->filter(fn (SplFileInfo $file) => !str_contains($file->getFilename(), 'exception'));
        }

        return $files->toArray();
    }

    private function prepareTheServiceName(): void
    {
        $this->service_name = str($this->service_name)->trim()->studly();
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
}
