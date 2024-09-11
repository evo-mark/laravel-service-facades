<?php

namespace EvoMark\EvoLaravelServiceFacades\Facades;

use EvoMark\EvoLaravelServiceFacades\Services\LocationService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerLocation(string $name, string $serviceNamespace, string $facadeNamespace, string $servicePath, string $facadePath, array $exclude = [])
 *
 * @see \EvoMark\EvoLaravelServiceFacades\Services\LocationService
 */
class ServiceFacades extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return LocationService::class;
    }
}
