<?php

namespace Shreifelagamy\LaravelServices\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\SplFileInfo;

class GenerateServiceCommand extends Command
{
    private Filesystem $filesystem;

    private string $service_name;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->service_name = $this->ask('What is the name of the service?');

        $this->prepareTheServiceName();

        // Check if the service already exists
        if ($this->filesystem->exists($this->getServicesPath() . '/' . $this->service_name)) {
            $this->output->error("Service {$this->service_name} already exists");
            return;
        }

        $this->createSerivceDirectoryStructure();

        $this->generateServiceFiles();
    }

    /**
     **
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVariables(): array
    {
        return [
            '$REPO_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Repositories",
            '$PROVIDER_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Providers",
            '$FACADE_NAMESPACE$' => "App\\Services\\{$this->service_name}\\Facades",
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

    private function generateServiceFiles(): void
    {
        foreach ($this->getStubFiles() as $file) {
            $this->generateServiceFile($file);
        }
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
        $this->output->success("{$file_path} created successfully");
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
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    private function getStubFiles(): array
    {
        return $this->filesystem->files($this->getStubPath());
    }

    private function prepareTheServiceName(): void
    {
        $this->service_name = str($this->service_name)->trim()->studly();
    }

    private function createSerivceDirectoryStructure(): void
    {
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Repositories', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Facades', 0755, true);
        $this->filesystem->makeDirectory($this->getServicesPath() . '/' . $this->service_name . '/Providers', 0755, true);
    }
}
