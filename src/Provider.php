<?php

namespace EvoMark\EvoLaravelServiceFacades;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use EvoMark\EvoLaravelServiceFacades\Services\LocationService;

class Provider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package->name('evo-laravel-service-facades')
            ->hasConfigFile('evo-service-facades')
            ->hasCommands([
                \EvoMark\EvoLaravelServiceFacades\Commands\MakeServiceCommand::class,
                \EvoMark\EvoLaravelServiceFacades\Commands\MakeFacadeCommand::class,
                \EvoMark\EvoLaravelServiceFacades\Commands\AnnotateFacadesCommand::class,
                \EvoMark\EvoLaravelServiceFacades\Commands\ClearLocationCacheCommand::class,
            ]);
    }

    public function registeringPackage()
    {
        $this->app->singleton(LocationService::class, fn(Application $app) => new LocationService($app));
    }

    private function getServiceClassesFromLocation($location): Collection
    {
        return Cache::remember(Constants::CLASS_LIST . $location['name'], 60 * 60 * 24, function () use ($location) {
            $classes = collect();
            foreach (Finder::create()->in($location['service_path'])->files() as $file) {
                $class = str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($file->getRealPath(), realpath($location['service_path']) . DIRECTORY_SEPARATOR)
                );
                if (isset($location['exclude']) && in_array($class, $location['exclude'])) {
                    continue;
                }

                $fullClass = $location['service_namespace'] . "\\" . $class;
                $classes->push($fullClass);
            }
            return $classes;
        });
    }

    public function bootingPackage()
    {
        $service = app(LocationService::class);
        $service->registerBasePath($this->package->basePath);

        foreach ($service->getLocations() as $location) {
            if (!file_exists($location['service_path'])) {
                continue;
            }

            $classes = $this->getServiceClassesFromLocation($location);
            foreach ($classes as $class) {
                $this->app->singletonIf($class, fn() => new $class);
            }
        }
    }

    public function packageBooted() {}
}
