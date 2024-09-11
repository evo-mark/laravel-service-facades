<?php

namespace EvoMark\EvoLaravelServiceFacades\Services;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Foundation\Application;

class LocationService
{
    protected Collection $locations;
    protected string $path;
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->locations = collect(config('evo-service-facades.locations'));
    }

    public function getBasePath()
    {
        return dirname($this->path);
    }

    public function registerBasePath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function registerLocation(
        string $name,
        string $serviceNamespace,
        string $facadeNamespace,
        string $servicePath,
        string $facadePath,
        array $exclude = []
    ): void {
        $this->locations->push([
            'name' => $name,
            'service_namespace' => $serviceNamespace,
            'facade_namespace' => $facadeNamespace,
            'service_path' => $servicePath,
            'facade_path' => $facadePath,
            'exclude' => $exclude
        ]);
    }

    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function getLocationNames(): Collection
    {
        return $this->locations->keyBy('name');
    }

    public function findLocation($name): array
    {
        return $this->locations->firstWhere('name', $name);
    }
}
