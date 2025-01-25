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

    protected function getDefaultNamespace($rootNamespace = "")
    {
        return $this->selectedLocation['facade_namespace'];
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        $key = $this->argument('class');

        $stub = str_replace('{{service_class}}', $key, $stub);
        $namespace = $this->selectedLocation['facade_namespace'];

        $replaceEnd = $this->selectedLocation['facade_class_replace_end'] ?? config('evo-service-facades.facade_class_replace_end');

        if (!empty($replaceEnd)) {
            list($search, $replace) = $replaceEnd;
            if (!empty($search)) {
                $class = Str::replaceEnd($search, $replace, $class);
            }
        }

        // Do string replacement
        return str($stub)->replace('{{facade_name}}', $class)->replace('{{facade_namespace}}', $namespace);
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->getDefaultNamespace(), '', $name);
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
