<?php

namespace EvoMark\EvoLaravelServiceFacades\Commands;

use Illuminate\Console\Command;
use EvoMark\EvoLaravelServiceFacades\Constants;
use EvoMark\EvoLaravelServiceFacades\Services\LocationService;
use Illuminate\Support\Facades\Cache;
use function Laravel\Prompts\multiselect;

class ClearLocationCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evo:service-facades-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the location cache';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $noInteraction = $this->option('no-interaction');

        $service = app(LocationService::class);
        $locationOptions = $service->getLocationNames();

        if ($noInteraction === true) {
            $selectedLocations = $locationOptions->keys();
        } else {
            $selectedLocations = multiselect(
                label: 'Which locations should be included?',
                options: $locationOptions->keys(),
                default: $locationOptions->keys(),
                hint: 'Deselect as required',
                required: true
            );
        }

        foreach ($service->getLocations() as $location) {
            if (!in_array($location['name'], $selectedLocations)) {
                continue;
            }
            Cache::forget(Constants::CLASS_LIST . $location['name']);
        }

        $this->info('Cleared cached classes for ' . implode(", ", $selectedLocations));
    }
}
