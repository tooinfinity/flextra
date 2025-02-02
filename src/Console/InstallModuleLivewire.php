<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

trait InstallModuleLivewire
{
    /**
     * Install the Livewire flextra stack.
     *
     * @return int|null
     */
    protected function installLivewireModule(string $moduleName, $functional): ?int
    {
        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@tailwindcss/forms' => '^0.5.2',
                'autoprefixer' => '^10.4.2',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.1.0',
            ] + $packages;
        });

        // Install Livewire...
        if (! $this->requireComposerPackages(['livewire/livewire:^3.4', 'livewire/volt:^1.0'])) {
            return 1;
        }

        // Install Volt...
        (new Process([$this->phpBinary(), 'artisan', 'volt:install'], base_path()))
            ->setTimeout(null)
            ->run();

        // Controllers
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/Http/Controllers/Auth/VerifyEmailController.php',
            base_path('Modules/'.$moduleName.'/app/Http/Controllers/Auth/VerifyEmailController.php')
        );

        // Views...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views',
            base_path('Modules/'.$moduleName.'/resources/views')
        );

        // Livewire Components...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/'($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire',
            base_path('Modules/'.$moduleName.'/resources/views/livewire')
        );

        // Views Components...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/resources/views/components',
            base_path('Modules/'.$moduleName.'/resources/views/components')
        );
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/components',
            base_path('Modules/'.$moduleName.'/resources/views/components')
        );

        // Views Layouts...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/layouts',
            base_path('Modules/'.$moduleName.'/resources/views/layouts')
        );

        // Components...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/View/Components',
            base_path('Modules/'.$moduleName.'/app/View/Components')
        );

        // Actions...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/app/Livewire/Actions',
            base_path('Modules/'.$moduleName.'/app/Livewire/Actions')
        );

        // Forms...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/app/Livewire/Forms',
            base_path('Modules/'.$moduleName.'/app/Livewire/Forms')
        );

        // Dark mode...
        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('Modules/'.$moduleName.'/resources/views'))
                ->name('*.blade.php')
                ->notPath('livewire/welcome/navigation.blade.php')
                ->notName('welcome.blade.php')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        $this->copyModuleFilesWithNamespaceLivewire(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/routes',
            base_path('Modules/'.$moduleName.'/routes')
        );
        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/blade-module/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/vite.config.js', base_path('vite.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/resources/css/app.css', resource_path('css/app.css'));

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lock')) || file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->components->info('Flextra Modules '.$moduleName.'  Livewire scaffolding installed successfully.');
    }

    private function copyModuleFilesWithNamespaceLivewire(string $moduleName, string $stubPath, string $targetPath): void
    {
        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists($targetPath);

        // Get all files from the stub path, recursively
        $files = (new Filesystem)->allFiles($stubPath);

        foreach ($files as $file) {
            // Get the relative path of the file within the stub directory
            $relativePath = $file->getRelativePath();

            // Create the target directory structure if it doesn't exist
            $targetDir = $targetPath.($relativePath ? '/'.$relativePath : '');
            $filesystem->ensureDirectoryExists($targetDir);

            // Read and replace placeholders in the file contents
            $contents = file_get_contents($file->getPathname());
            $contents = str_replace('{{moduleName}}', $moduleName, $contents);
            $contents = str_replace('{{moduleNameLower}}', strtolower($moduleName), $contents);

            // Write the modified contents to the target file, preserving the directory structure
            $filesystem->put(
                $targetDir.'/'.$file->getFilename(),
                $contents
            );
        }
    }
}
