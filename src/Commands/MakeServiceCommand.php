<?php

namespace EvoMark\EvoLaravelServiceFacades\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use function Laravel\Prompts\select;
use Illuminate\Console\GeneratorCommand;
use EvoMark\EvoLaravelServiceFacades\Provider;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use function Illuminate\Filesystem\join_paths as join_paths;
use EvoMark\EvoLaravelServiceFacades\Services\LocationService;

class MakeServiceCommand extends GeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    protected $description = 'Create a new service with a backing Facade';

    protected $type = 'Service';

    protected array $selectedLocation;

    protected function getStub()
    {
        $basePath = app(LocationService::class)->getBasePath();
        return join_paths($basePath, 'stubs/service.php.stub');
    }

    protected function rootNamespace()
    {
        return $this->selectedLocation['service_namespace'];
    }

    protected function getDefaultNamespace($rootNamespace = "")
    {
        return $this->selectedLocation['service_namespace'];
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        $namespace = $this->selectedLocation['service_namespace'];

        // Do string replacement
        return str($stub)->replace('{{service_name}}', $class)->replace('{{service_namespace}}', $namespace)->value;
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->getDefaultNamespace(), '', $name);
        return join_paths($this->selectedLocation['service_path'], str_replace('\\', '/', $name) . '.php');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(LocationService::class);
        $locations = $service->getLocationNames();

        if ($locations->count() > 1) {
            $locationName = select(
                label: 'Which location should be used?',
                options: $locations->keys()
            );
            $location = $locations[$locationName];
        } else {
            $location = $locations->first();
        }
        if (empty($location)) {
            throw new \Exception("Couldn't find valid location to use");
        }
        $this->selectedLocation = $location;

        $this->input->setArgument('name', Str::finish($this->argument('name'), 'Service'));
        $absoluteClass = '\\' . $this->qualifyClass($this->argument('name')) . '::class';

        parent::handle();

        $this->call('make:facade', [
            'name' => $this->argument('name'),
            'class' => $absoluteClass,
            'location' => $location['name']
        ]);
    }
}
