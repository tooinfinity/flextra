<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

final class Common
{
    private const SUPPORTED_STACKS = [
        'react', 'react-ts',
        'vue', 'vue-ts',
        'svelte', 'svelte-ts',
    ];

    private Filesystem $fileSystem;

    private string $moduleName;

    private string $stack;

    public function __construct(string $moduleName, string $stack = 'react')
    {
        $this->fileSystem = new Filesystem;
        $this->moduleName = $moduleName;
        $this->validateStack($stack);
        $this->stack = $stack;
    }

    public function authBackendFiles(): void
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

        // Views...
        copy(
            $this->getStubPath().'/resources/views/app.blade.php',
            base_path('Modules/'.$this->moduleName.'/resources/views/app.blade.php')
        );

        @unlink(resource_path('views/welcome.blade.php'));
        @unlink(base_path('Modules/'.$this->moduleName.'/resources/views/welcome.blade.php'));
    }

    public function authFrontendFiles(): void
    {
        $stubPath = $this->getStubPath();
        $modulePath = base_path('Modules/'.$this->moduleName);

        // Copy JS/TS files
        $this->copyFrontendDirectories($stubPath, $modulePath);

        // Copy configuration files
        $this->copyConfigFiles($stubPath, $modulePath);

        // Copy CSS files
        $this->copyCssFiles($stubPath, $modulePath);

        // Copy TypeScript specific files if using a TypeScript stack
        if (str_contains($this->stack, '-ts')) {
            $this->copyTypeScriptFiles($stubPath, $modulePath);
        }
    }

    private function validateStack(string $stack): void
    {
        if (! in_array($stack, self::SUPPORTED_STACKS)) {
            throw new InvalidArgumentException(
                "Invalid stack: {$stack}. Supported stacks are: ".implode(', ', self::SUPPORTED_STACKS)
            );
        }
    }

    private function getStubPath(): string
    {
        // Convert react-ts to react-typescript for directory structure
        $stack = str_replace('-ts', '-typescript', $this->stack);

        return __DIR__."/../../stubs/inertia-{$stack}";
    }

    private function copyTypeScriptFiles(string $stubPath, string $modulePath): void
    {
        $tsFiles = [
            'tsconfig.json',
            'types/index.d.ts',
            'types/global.d.ts',
        ];

        foreach ($tsFiles as $file) {
            $sourcePath = "{$stubPath}/{$file}";
            $destPath = "{$modulePath}/{$file}";

            if (file_exists($sourcePath)) {
                $this->fileSystem->ensureDirectoryExists(dirname($destPath));
                copy($sourcePath, $destPath);
            }
        }
    }

    private function copyFrontendDirectories(string $stubPath, string $modulePath): void
    {
        $directories = ['Components', 'Layouts', 'Pages', 'Types'];
        $extension = str_contains($this->stack, '-ts') ? '{ts,tsx}' : '{js,jsx}';

        foreach ($directories as $dir) {
            if ($dir === 'Types' && ! str_contains($this->stack, '-ts')) {
                continue;
            }

            $sourceDir = "{$stubPath}/resources/js/{$dir}";
            $destDir = "{$modulePath}/resources/js/{$dir}";

            if (is_dir($sourceDir)) {
                $this->fileSystem->copyDirectory($sourceDir, $destDir);
            }
        }
    }

    private function copyConfigFiles(string $stubPath, string $modulePath): void
    {
        $configFiles = [
            'postcss.config.js',
            'tailwind.config.js',
            'vite.config.js',
            'package.json',
        ];

        // Add TypeScript specific config files if using TypeScript
        if (str_contains($this->stack, '-ts')) {
            $configFiles[] = 'tsconfig.json';
        }

        $this->fileSystem->ensureDirectoryExists($modulePath);

        foreach ($configFiles as $file) {
            if (file_exists("{$stubPath}/{$file}")) {
                copy("{$stubPath}/{$file}", "{$modulePath}/{$file}");
            }
        }
    }

    private function copyCssFiles(string $stubPath, string $modulePath): void
    {
        $cssPath = "{$modulePath}/resources/css";
        $this->fileSystem->ensureDirectoryExists($cssPath);

        copy(
            "{$stubPath}/resources/css/app.css",
            "{$cssPath}/app.css"
        );
    }

    private function copyModuleFilesWithNamespace(string $moduleName, string $source, string $destination): void
    {
        $this->fileSystem->ensureDirectoryExists($destination);

        foreach ($this->fileSystem->allFiles($source) as $file) {
            $contents = file_get_contents($file->getPathname());

            // Replace namespace
            $contents = str_replace(
                'namespace App\\',
                'namespace Modules\\'.$moduleName.'\\App\\',
                $contents
            );

            $this->fileSystem->put($destination.'/'.$file->getFilename(), $contents);
        }
    }

    private function installMiddleware(array $middleware): void
    {
        $kernelPath = app_path('Http/Kernel.php');
        $kernelContents = file_get_contents($kernelPath);

        foreach ($middleware as $middlewareClass) {
            if (! str_contains($kernelContents, $middlewareClass)) {
                $pattern = "/('web' => \[\n\s*)/";
                $replacement = "$1        $middlewareClass,\n        ";
                $kernelContents = preg_replace($pattern, $replacement, $kernelContents);
            }
        }

        file_put_contents($kernelPath, $kernelContents);
    }
}
