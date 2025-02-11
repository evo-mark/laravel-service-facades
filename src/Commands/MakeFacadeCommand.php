<?php

namespace EvoMark\EvoLaravelServiceFacades\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use function Illuminate\Filesystem\join_paths as join_paths;
use EvoMark\EvoLaravelServiceFacades\Services\LocationService;


class MakeFacadeCommand extends GeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:facade {name} {class} {location}';

    protected $description = 'Create a new facade for a service class';

    protected $type = 'Facade';

    protected array $selectedLocation;

    protected function getStub()
    {
        $basePath = app(LocationService::class)->getBasePath();
        return join_paths($basePath, 'stubs/facade.php.stub');
    }

    protected function rootNamespace()
    {
        return $this->selectedLocation['service_namespace'];
    }

    protected function getDefaultNamespace($rootNamespace = "")
    {
        return $this->selectedLocation['facade_namespace'];
    }

    private function replaceClassEnd(string $class): string
    {
        $replaceEnd = $this->selectedLocation['facade_class_replace_end'] ?? config('evo-service-facades.facade_class_replace_end');

        if (!empty($replaceEnd)) {
            list($search, $replace) = $replaceEnd;
            if (!empty($search)) {
                $class = Str::replaceEnd($search, $replace, $class);
            }
        }

        return $class;
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        $class = $this->replaceClassEnd($class);
        $key = $this->argument('class');

        $stub = str_replace('{{service_class}}', $key, $stub);
        $namespace = $this->selectedLocation['facade_namespace'];

        return str($stub)->replace('{{facade_name}}', $class)->replace('{{facade_namespace}}', $namespace);
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->getDefaultNamespace(), '', $name);
        $name = $this->replaceClassEnd($name);
        return join_paths($this->selectedLocation['facade_path'], str_replace('\\', '/', $name) . '.php');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(LocationService::class);
        $locationName = $this->argument('location');
        $this->selectedLocation = $service->findLocation($locationName);
        parent::handle();
    }
}
