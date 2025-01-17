<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

final readonly class Common
{
    private const SUPPORTED_STACKS = [
        'common',
        'react', 'react-ts',
        'vue', 'vue-ts',
        'svelte', 'svelte-ts',
    ];

    private Filesystem $fileSystem;

    private string $stack;

    private bool $isTypeScript;

    public function __construct(private string $moduleName, string $stack = 'react')
    {
        $this->fileSystem = new Filesystem;
        $this->validateStack($stack);
        $this->stack = $stack;
        $this->isTypeScript = str_contains($stack, '-ts');
    }

    public function installAuthBackendFiles(): void
    {
        // Providers...
        $this->fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-php/Providers', app_path('Providers'));

        // Controllers...
        $this->copyModuleFilesWithNamespace(
            $this->moduleName,
            __DIR__.'/../../stubs/inertia-php/Auth/Controllers',
            base_path('Modules/'.$this->moduleName.'/app/Http/Controllers')
        );
        $this->copyModuleFilesWithNamespace(
            $this->moduleName,
            __DIR__.'/../../stubs/inertia-php/Profile/Controllers',
            base_path('Modules/'.$this->moduleName.'/app/Http/Controllers')
        );
        // Requests...
        $this->copyModuleFilesWithNamespace(
            $this->moduleName,
            __DIR__.'/../../stubs/inertia-php/Auth/Requests',
            base_path('Modules/'.$this->moduleName.'/app/Http/Requests')
        );
        $this->copyModuleFilesWithNamespace(
            $this->moduleName,
            __DIR__.'/../../stubs/inertia-php/Profile/Requests',
            base_path('Modules/'.$this->moduleName.'/app/Http/Requests')
        );

        // Middleware...
        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        $this->fileSystem->ensureDirectoryExists(app_path('Http/Middleware'));
        copy(
            __DIR__.'/../../stubs/inertia-php/Middleware/HandleInertiaRequests.php',
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        @unlink(resource_path('views/welcome.blade.php'));
        @unlink(base_path('Modules/'.$this->moduleName.'/resources/views/welcome.blade.php'));

        // Routes...
        $this->copyFileWithNamespace($this->moduleName, __DIR__.'/../../stubs/inertia-php/routes/web.php', base_path('Modules/'.$this->moduleName.'/routes/web.php'));
        $this->copyFileWithNamespace($this->moduleName, __DIR__.'/../../stubs/inertia-php/routes/auth.php', base_path('Modules/'.$this->moduleName.'/routes/auth.php'));
    }

    public function installAuthFrontendFiles(): void
    {
        $stubPath = $this->getStubPath();
        $modulePath = "Modules/{$this->moduleName}";

        // 1. Copy Breeze pages to module
        $this->copyModulePages($stubPath, $modulePath);

        // 2. Copy Types to module if TypeScript
        if ($this->isTypeScript) {
            $this->copyModuleTypes($stubPath, $modulePath);
        }

        // 3. Copy Components to module
        $this->copyModuleComponents($stubPath, $modulePath);

        // 4. Setup main app files
        $this->setupMainAppFiles($stubPath);

        // 5. Setup config files
        $this->setupConfigFiles($stubPath);
    }

    private function copyModulePages(string $stubPath, string $modulePath): void
    {
        $this->fileSystem->ensureDirectoryExists(base_path("{$modulePath}/resources/assets/js/Pages"));
        $this->fileSystem->copyDirectory(
            "{$stubPath}/resources/js/Pages",
            base_path("{$modulePath}/resources/assets/js/Pages")
        );
    }

    private function copyModuleTypes(string $stubPath, string $modulePath): void
    {
        $this->fileSystem->ensureDirectoryExists(base_path("{$modulePath}/resources/assets/js/types"));
        $this->fileSystem->copyDirectory(
            "{$stubPath}/resources/js/types",
            base_path("{$modulePath}/resources/assets/js/types")
        );
    }

    private function copyModuleComponents(string $stubPath, string $modulePath): void
    {
        $this->fileSystem->ensureDirectoryExists(base_path("{$modulePath}/resources/assets/js/Components"));
        $this->fileSystem->copyDirectory(
            "{$stubPath}/resources/js/Components",
            base_path("{$modulePath}/resources/assets/js/Components")
        );
    }

    private function setupMainAppFiles(string $stubPath): void
    {
        // Copy app.blade.php to main Laravel views
        $this->fileSystem->ensureDirectoryExists(resource_path('views'));
        copy(
            "{$stubPath}/resources/views/app.blade.php",
            resource_path('views/app.blade.php')
        );

        // Copy and modify app entry file
        $extension = $this->getEntryFileExtension();
        $this->fileSystem->ensureDirectoryExists(resource_path('js'));
        $this->copyAndModifyAppEntry($stubPath, $extension);
    }

    private function setupConfigFiles(string $stubPath): void
    {
        // Copy and modify vite config
        copy(
            "{$stubPath}/vite.config.js",
            base_path('vite.config.js')
        );
        $this->modifyViteConfig();

        // Copy and modify TS/JS config
        if ($this->isTypeScript) {
            copy(
                "{$stubPath}/tsconfig.json",
                base_path('tsconfig.json')
            );
            $this->modifyTsConfig();
        } else {
            copy(
                "{$stubPath}/jsconfig.json",
                base_path('jsconfig.json')
            );
            $this->modifyJsConfig();
        }
    }

    private function copyAndModifyAppEntry(string $stubPath, string $extension): void
    {
        $content = file_get_contents("{$stubPath}/resources/js/app.{$extension}");

        // Modify the content to include dynamic module imports
        $content = $this->modifyAppEntryContent($content);

        file_put_contents(
            resource_path("js/app.{$extension}"),
            $content
        );
    }

    private function modifyAppEntryContent(string $content): string
    {
        $moduleImports = <<<'EOT'
        // Dynamic module imports
        const modules = import.meta.glob('/Modules/*/resources/assets/js/Pages/**/*.{jsx,tsx,vue,svelte}', {
            eager: true
        });
        
        // Register global components
        const components = import.meta.glob('/Modules/*/resources/assets/js/Components/**/*.{jsx,tsx,vue,svelte}', {
            eager: true
        });
        EOT;

        // Insert the module imports based on the stack
        if (str_contains($this->stack, 'vue')) {
            return str_replace(
                'createApp({',
                "{$moduleImports}\n\nconst app = createApp({",
                $content
            );
        } elseif (str_contains($this->stack, 'react')) {
            return str_replace(
                'createInertiaApp({',
                "{$moduleImports}\n\ncreateInertiaApp({",
                $content
            );
        } else { // svelte
            return str_replace(
                'createInertiaApp({',
                "{$moduleImports}\n\ncreateInertiaApp({",
                $content
            );
        }
    }

    private function modifyViteConfig(): void
    {
        $content = file_get_contents(base_path('vite.config.js'));

        $moduleConfig = <<<'EOT'
            input: [
                'resources/js/app.js',
                'Modules/*/resources/assets/js/app.js'
            ],
            resolve: {
                alias: {
                    '@': '/resources/js',
                    '@modules': '/Modules'
                }
            },
        EOT;

        $content = str_replace(
            "input: 'resources/js/app.js',",
            $moduleConfig,
            $content
        );

        file_put_contents(base_path('vite.config.js'), $content);
    }

    /* private function modifyTsConfig(): void
     {
         $content = json_decode(file_get_contents(base_path('tsconfig.json')), true);

         $content['compilerOptions']['paths'] = array_merge(
             $content['compilerOptions']['paths'] ?? [],
             [
                 "@modules/*": ['./Modules/*"/\resources/assets/\"js/*'],
                 "@/*": ["./resources/js/*"]
             ]
         );

         file_put_contents(
             base_path('tsconfig.json'),
             json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
         );
     }*/

    /* private function modifyJsConfig(): void
     {
         $content = json_decode(file_get_contents(base_path('jsconfig.json')), true);

         $content['compilerOptions']['paths'] = array_merge(
             $content['compilerOptions']['paths'] ?? [],
             [
                 "@modules/*": ['./Modules/*"/\resources/assets/\"js/*'],
                 "@/*": ["./resources/js/*"]
             ]
         );

         file_put_contents(
             base_path('jsconfig.json'),
             json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
         );
     }*/

    private function getEntryFileExtension(): string
    {
        if (str_contains($this->stack, '-ts')) {
            return str_contains($this->stack, 'react') ? 'tsx' : 'ts';
        }

        return str_contains($this->stack, 'react') ? 'jsx' : 'js';
    }

    private function getStubPath(): string
    {
        return __DIR__."/../../stubs/inertia-{$this->stack}";
    }

    private function validateStack(string $stack): void
    {
        if (! in_array($stack, self::SUPPORTED_STACKS)) {
            throw new InvalidArgumentException(
                "Invalid stack: {$stack}. Supported stacks are: ".implode(', ', self::SUPPORTED_STACKS)
            );
        }
    }

    private function copyModuleFilesWithNamespace(string $moduleName, string $stubPath, string $targetPath): void
    {
        $filesystem = new Filesystem;

        // check if the target path exists
        $filesystem->ensureDirectoryExists($targetPath);
        // Get all files from the stub directory
        $files = (new Filesystem)->allFiles($stubPath);

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

    // function to copy one file from stub to target path with Replace the moduleName placeholder in the contents
    private function copyFileWithNamespace(string $moduleName, string $stubfile, string $targetfile): void
    {
        $contents = file_get_contents($stubfile);
        $contents = str_replace('{{moduleName}}', $moduleName, $contents);
        file_put_contents($targetfile, $contents);
    }

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
}
