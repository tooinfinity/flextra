<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

#[AsCommand(name: 'flextra:install')] // Flextra: Inspired by "Flexibility" and "Extra", perfect for multi-framework tools.
final class InstallCommand extends Command implements PromptsForMissingInput
{
    use InstallReactWithInertia, InstallSvelteWithInertia, InstallVueWithInertia;

    /**
     * The console command signature and name.
     *
     * @var string
     */
    protected $signature = 'flextra:install {stack? : The Development stack that should be installed with laravel Modules (react, vue, svelte)}
                            {--dark : Indicate that dark mode support should be installed}
                            {--pest : Indicate that Pest should be installed}
                            {--ssr : Indicates if Inertia SSR support should be installed}
                            {--typescript : Indicates if TypeScript is preferred for the Inertia stack}
                            {--eslint : Indicates if ESLint with Prettier should be installed}
                            {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the flextra with the Inertia stack and laravel Modules';

    /**
     * The name of the module.
     * I prefer auth because it's a must-have module for breeze to live
     */
    private string $moduleName = 'Auth';

    /**
     * Execute the console command.
     */
    public function handle(): ?int
    {
        if ($this->argument('stack') === 'vue') {
            return $this->installModuleInertiaVue($this->moduleName);
        } elseif ($this->argument('stack') === 'react') {
            return $this->installModuleInertiaReact($this->moduleName);
        } elseif ($this->argument('stack') === 'svelte') {
            return $this->installModuleInertiaSvelte($this->moduleName);
        }

        $this->components->error('Invalid stack. Supported stacks are [react], [vue], and [svelte].');

        return 1;
    }

    protected function copyModuleFilesWithNamespace(string $moduleName, string $stubPath, string $targetPath): void
    {
        $filesystem = new Filesystem;

        // check if the target path exists
        if (! File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        // Get all files from the stub directory
        $files = $filesystem->allFiles($stubPath);

        foreach ($files as $file) {
            $contents = file_get_contents($file->getPathname());

            // Replace the moduleName placeholder in the contents
            $contents = str_replace('{{moduleName}}', $moduleName, $contents);

            // Create the target file with replaced contents
            $filesystem->put(
                $targetPath.'/'.$file->getFilename(),
                $contents
            );
        }
    }

    /**
     * Prompt for missing arguments to the command.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'stack' => fn (): string => select(
                label: 'Which stack do you want to install?',
                options: [
                    'react' => 'Inertia React with Laravel Modules',
                    'vue' => 'Inertia Vue with Laravel Modules',
                    'svelte' => 'Inertia Svelte with Laravel Modules',
                ],
                scroll: 3,
            ),
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $stack = $input->getArgument('stack');

        if (in_array($stack, ['react', 'vue', 'svelte'])) {
            collect(multiselect(
                label: 'Would you like any optional features?',
                options: [
                    'dark' => 'Dark mode',
                    'ssr' => 'Inertia SSR',
                    'typescript' => 'TypeScript',
                    'eslint' => 'ESLint with Prettier',
                ],
                hint: 'Use the space bar to select options.'
            ))->each(fn ($option) => $input->setOption($option, true));
        }

        $input->setOption('pest', select(
            label: 'Which testing framework do you prefer?',
            options: ['Pest', 'PHPUnit'],
            default: 'Pest',
        ) === 'Pest');
    }

    /**
     * Update the dependencies in the "package.json" file.
     */
    private function updateNodePackages(callable $callback, bool $dev = true): void
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Update the scripts in the "package.json" file.
     */
    private function updateNodeScripts(callable $callback): void
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $content = json_decode(file_get_contents(base_path('package.json')), true);

        $content['scripts'] = $callback(
            array_key_exists('scripts', $content) ? $content['scripts'] : []
        );

        file_put_contents(
            base_path('package.json'),
            json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Delete the "node_modules" directory and remove the associated lock files.
     */
    private function flushNodeModules(): void
    {
        tap(new Filesystem, function ($files): void {
            $files->deleteDirectory(base_path('node_modules'));

            $files->delete(base_path('pnpm-lock.yaml'));
            $files->delete(base_path('yarn.lock'));
            $files->delete(base_path('bun.lockb'));
            $files->delete(base_path('deno.lock'));
            $files->delete(base_path('package-lock.json'));
        });
    }

    /**
     * Installs the given Composer Packages into the application.
     */
    private function requireComposerPackages(array $packages, bool $asDev = false): bool
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = ['php', $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            $packages,
            $asDev ? ['--dev'] : [],
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output): void {
                $this->output->write($output);
            }) === 0;
    }

    /**
     * Replace a given string within a given file.
     */
    private function replaceInFile(string $search, string $replace, string $path): void
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Run the given stack commands.
     */
    private function runCommands(array $commands): void
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }
        $process->run(function ($type, string $line): void {
            $this->output->write('    '.$line);
        });
    }

    /**
     * install module-inertia-react tests from stub
     */
    private function installTests(): bool
    {
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/tests/Feature'));

        $stubStack = match ($this->argument('stack')) {
            'react' => 'react',
            'vue' => 'vue',
            'svelte' => 'svelte',
            default => throw new InvalidArgumentException('Invalid stack. Supported stacks are [react], [vue], and [svelte].'),
        };
        if ($this->option('pest') || $this->isUsingPest()) {
            if ($this->hasComposerPackage('phpunit/phpunit')) {
                $this->removeComposerPackage((array) 'phpunit/phpunit', true);
            }
            if (! $this->requireComposerPackages(['pestphp/pest', 'pestphp/pest-plugin-laravel'], true)) {
                return false;
            }
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/'.$stubStack.'/pest-tests/Feature', base_path('Modules/'.$moduleName.'/tests/Feature'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/'.$stubStack.'/pest-tests/Unit', base_path('Modules/'.$moduleName.'/tests/Unit'));
            (new Filesystem)->copy(__DIR__.'/../../stubs/'.$stubStack.'/pest-tests/Pest.php', base_path('Modules/'.$moduleName.'/tests/Pest.php'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/'.$stubStack.'/tests/Feature', base_path('Modules/'.$moduleName.'/tests/Feature'));
        }

        return true;
    }

    /**
     * Install the given middleware names into the application.
     */
    private function installMiddleware(array|string $names, string $group = 'web', string $modifier = 'append'): void
    {
        $bootstrapApp = file_get_contents(base_path('bootstrap/app.php'));

        collect(Arr::wrap($names))
            ->filter(fn ($name): bool => ! Str::contains($bootstrapApp, $name))
            ->whenNotEmpty(function ($names) use ($bootstrapApp, $group, $modifier): void {
                $names = $names->map(fn ($name): string => "$name")->implode(','.PHP_EOL.'            ');

                $bootstrapApp = str_replace(
                    '->withMiddleware(function (Middleware $middleware) {',
                    '->withMiddleware(function (Middleware $middleware) {'
                    .PHP_EOL."        \$middleware->$group($modifier: ["
                    .PHP_EOL."            $names,"
                    .PHP_EOL.'        ]);'
                    .PHP_EOL,
                    $bootstrapApp,
                );

                file_put_contents(base_path('bootstrap/app.php'), $bootstrapApp);
            });
    }

    /**
     * Install the given middleware aliases into the application.
     */
    private function installMiddlewareAliases(array $aliases): void
    {
        $bootstrapApp = file_get_contents(base_path('bootstrap/app.php'));

        collect($aliases)
            ->filter(fn ($alias): bool => ! Str::contains($bootstrapApp, $alias))
            ->whenNotEmpty(function ($aliases) use ($bootstrapApp): void {
                $aliases = $aliases->map(fn ($name, $alias): string => "'$alias' => $name")->implode(','.PHP_EOL.'            ');

                $bootstrapApp = str_replace(
                    '->withMiddleware(function (Middleware $middleware) {',
                    '->withMiddleware(function (Middleware $middleware) {'
                    .PHP_EOL.'        $middleware->alias(['
                    .PHP_EOL."            $aliases,"
                    .PHP_EOL.'        ]);'
                    .PHP_EOL,
                    $bootstrapApp,
                );

                file_put_contents(base_path('bootstrap/app.php'), $bootstrapApp);
            });
    }

    /**
     * Configure Ziggy for SSR.
     */
    private function configureZiggyForSsr(): void
    {
        $this->replaceInFile(
            <<<'EOT'
            use Inertia\Middleware;
            EOT,
            <<<'EOT'
            use Inertia\Middleware;
            use Tighten\Ziggy\Ziggy;
            EOT,
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        $this->replaceInFile(
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
            EOT,
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
                        'ziggy' => fn () => [
                            ...(new Ziggy)->toArray(),
                            'location' => $request->url(),
                        ],
            EOT,
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        if ($this->option('typescript')) {
            $this->replaceInFile(
                <<<'EOT'
                export interface User {
                EOT,
                <<<'EOT'
                import { Config } from 'ziggy-js';

                export interface User {
                EOT,
                resource_path('js/types/index.d.ts')
            );

            $this->replaceInFile(
                <<<'EOT'
                    auth: {
                        user: User;
                    };
                EOT,
                <<<'EOT'
                    auth: {
                        user: User;
                    };
                    ziggy: Config & { location: string };
                EOT,
                resource_path('js/types/index.d.ts')
            );
        }
    }

    /**
     * Remove Tailwind dark classes from the given files.
     */
    private function removeDarkClasses(Finder $finder): void
    {
        foreach ($finder as $file) {
            file_put_contents($file->getPathname(), preg_replace('/\sdark:[^\s"\']+/', '', $file->getContents()));
        }
    }

    /**
     * Install Module Dependencies
     */
    private function installModuleDependencies(): void
    {
        // Check if laravel Modules installed and install it
        if (! InstalledVersions::isInstalled('nwidart/laravel-modules')) {
            $this->addToComposer('config', 'allow-plugins', [
                'wikimedia/composer-merge-plugin' => true,
                'pestphp/pest-plugin' => true,
            ]);
            $this->runCommands(['composer require nwidart/laravel-modules']);
            $this->runCommands(['php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"']);
            $this->addToComposer('extra', 'merge-plugin', [
                'include' => [
                    'Modules/*/composer.json',
                ],
            ]);
            $this->runCommands(['composer dump-autoload']);
        }
        // Prompt the user to either make the Auth module but, I prefer auth
        // because it's a must-have module for breeze to live
        $moduleNameInput = $this->ask('Enter the name of the module');
        $this->moduleName = $moduleNameInput ?? $this->moduleName;
        $this->runCommands(["php artisan module:make {$this->moduleName}"]);
    }

    /**
     * Adding code to composer Extra
     */
    private function addToComposer(string $parent, string $key, array $value): void
    {
        $composerJsonPath = base_path('composer.json');

        // Read and decode composer.json
        $composerJson = json_decode(File::get($composerJsonPath), true);

        // Ensure the 'extra' section exists
        if (! isset($composerJson[$parent])) {
            $composerJson[$parent] = [];
        }

        // Add or update the key-value pair
        $composerJson[$parent][$key] = $value;

        // Encode the array back to JSON and save it
        File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Determine if the given Composer package is installed.
     */
    private function hasComposerPackage(string $package): bool
    {
        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        return array_key_exists($package, $packages['require'] ?? [])
            || array_key_exists($package, $packages['require-dev'] ?? []);
    }

    /**
     * Remove the given Composer packages from the application.
     */
    private function removeComposerPackage(array $packages, bool $asDev = false): bool
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = ['php', $composer, 'remove'];
        }

        $command = array_merge(
            $command ?? ['composer', 'remove'],
            $packages,
            $asDev ? ['--dev'] : [],
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output): void {
                $this->output->write($output);
            }) === 0;
    }

    /**
     * Determine if the application is using Pest.
     */
    private function isUsingPest(): bool
    {
        return class_exists(\Pest\TestSuite::class);
    }
}
