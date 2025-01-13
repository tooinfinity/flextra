<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;

trait InstallSvelteWithInertia
{
    /**
     * Install the inertia stack with Laravel module using Svelte.
     */
    public function installModuleInertiaSvelte(string $moduleName): ?int
    {
        // Install Inertia and Laravel Modules
        if (! $this->requireComposerPackages([
            'inertiajs/inertia-laravel:^2.0',
            'tightenco/ziggy:^2.0',
            'laravel/sanctum:^4.0',
        ])) {
            return 1;
        }

        // Install or update NPM packages
        $this->updateNodePackages(fn ($packages) => [
            '@inertiajs/svelte' => '^0.13.0',
            '@vitejs/plugin-svelte' => '^4.0.0',
            'autoprefixer' => '^10.4.12',
            'postcss' => '^8.4.31',
            'svelte' => '^4.0.0',
            'tailwindcss' => '^3.2.1',
        ] + $packages);

        // Providers...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Providers', app_path('Providers'));

        // Middleware...
        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        (new Filesystem)->ensureDirectoryExists(app_path('Http/Middleware'));
        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', app_path('Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-svelte/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Pages', resource_path('js/Pages'));

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-svelte/vite.config.js', base_path('vite.config.js'));

        // Install or update NPM packages for SSR
        if ($this->option('ssr')) {
            $this->installModuleInertiaSvelteSsr();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Flextra Svelte scaffolding installed successfully.');

        return 0;
    }

    /**
     * Install the Inertia Svelte SSR stack into the application.
     */
    protected function installModuleInertiaSvelteSsr(): void
    {
        copy(__DIR__.'/../../stubs/inertia-svelte/resources/js/ssr.js', resource_path('js/ssr.js'));

        $this->replaceInFile(
            "input: 'resources/js/app.js',",
            "input: 'resources/js/app.js',".PHP_EOL."            ssr: 'resources/js/ssr.js',",
            base_path('vite.config.js')
        );

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }
}
