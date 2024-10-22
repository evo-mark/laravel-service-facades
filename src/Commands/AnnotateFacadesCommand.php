<?php

namespace EvoMark\EvoLaravelServiceFacades\Commands;

use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Console\Isolatable;
use EvoMark\EvoLaravelServiceFacades\Services\LocationService;
use function Illuminate\Filesystem\join_paths as join_paths;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;

class AnnotateFacadesCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facades:annotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate facade annotations based on the information from the backing service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $noInteraction = $this->option('no-interaction');

        $service = app(LocationService::class);
        $locationOptions = $service->getLocationNames();

        if ($noInteraction === true || count($locationOptions->keys()) === 1) {
            $selectedLocations = $locationOptions->keys();
        } else {
            $selectedLocations = collect(multiselect(
                label: 'Which locations should be included?',
                options: $locationOptions->keys(),
                default: $locationOptions->keys(),
                    hint: 'Deselect as required',
                    required: true
            ));
        }

        
        foreach ($service->getLocations() as $location) {
            if (!file_exists($location['facade_path']) || $selectedLocations->contains($location['name']) === false) {
                continue;
            }
            
            $facadeFiles = glob(join_paths($location['facade_path'], '*.php'));
            $progress = progress(label: 'Updating facade annotations for ' . $location['name'], steps: count($facadeFiles));
            
            $progress->start();

            foreach ($facadeFiles as $file) {
                $facade = self::getClassFullNameFromFile($file);
                $facadeBase = class_basename($facade);
                $accessor = get_class($facade::getFacadeRoot());

                $serviceReflection = new ReflectionClass($accessor);

                $serviceMethods = collect($serviceReflection->getMethods())
                    ->filter(fn($method) => ! Str::startsWith($method->getName(), '__'))
                    ->map(function ($method) {
                        $name = $method->getName();
                        $params = [];
                        foreach ($method->getParameters() as $param) {
                            $params[] = $param->getType() . ' $' . $param->getName();
                        }
                        $returns = $method->getReturnType()?->getName();

                        return [
                            'name' => $name,
                            'params' => $params,
                            'returns' => $returns,
                        ];
                    });

                $facadeContents = file_get_contents($file);
                $pattern = '/(\/\*\*[\w\W]*?\*\/)?\n+class\s+' . $facadeBase . '/';
                if (preg_match($pattern, $facadeContents, $matches)) {
                    $newContents = str_replace($matches[0], $this->generateDocBlock($accessor, $serviceMethods, $facadeBase), $facadeContents);
                    $newContents = preg_replace('/(\n{2})\n+/', "$1", $newContents);
                    if (! empty($facadeContents) && ! empty($newContents)) {
                        file_put_contents($file, $newContents);
                    } else {
                        $this->warn('Didn\'t update ' . $facadeBase . ' because there was an error');
                    }
                } else {
                    $this->warn('Failed to match insertion point for ' . $facadeBase);
                }

                $progress->advance();

            }

            $progress->finish();
        }

    }

    private function generateDocBlock(string $seeClassName, Collection $methods, string $className)
    {
        $output = "\n\n/**\n";

        foreach ($methods as $method) {
            $output .= ' * @method static ' . $this->generateReturns($method['returns']) . $method['name'] . '(';
            $output .= Arr::join($method['params'], ', ');
            $output .= ")\n";
        }

        $output .= " *\n";
        $output .= ' * @see \\' . $seeClassName . "\n";
        $output .= " */\nclass $className";

        return $output;
    }

    private function generateReturns(?string $returns = ''): string
    {
        if (empty($returns)) {
            return 'void ';
        } else {
            return (class_exists($returns) ? '\\' . $returns : $returns) . ' ';
        }
    }

    /**
     * Get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     */
    public static function getClassFullNameFromFile($filePathName): string
    {
        return self::getClassNamespaceFromFile($filePathName) . '\\' . self::getClassNameFromFile($filePathName);
    }

    /**
     * Build and return an object of a class from its file path
     */
    public static function getClassObjectFromFile($filePathName): mixed
    {
        $classString = self::getClassFullNameFromFile($filePathName);

        $object = new $classString;

        return $object;
    }

    /**
     * Get the class namespace form file path using token
     */
    protected static function getClassNamespaceFromFile($filePathName): ?string
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (! $namespace_ok) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * Get the class name form file path using token
     */
    protected static function getClassNameFromFile($filePathName): mixed
    {
        $php_code = file_get_contents($filePathName);

        $classes = [];
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if (
                $tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes[0];
    }
}
