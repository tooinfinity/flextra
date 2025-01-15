<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

trait InstallVueWithInertia
{
    /**
     * Install the Inertia Vue stack.
     */
    protected function installModuleInertiaVue(string $moduleName): int
    {
        // Install Module Dependencies first
        $this->installModuleDependencies();

        // Initialize Common with appropriate stack
        $stack = $this->option('typescript') ? 'vue-ts' : 'vue';
        $common = new Common($moduleName, $stack);

        // Install Inertia and its dependencies...
        $this->requireComposerPackages(['inertiajs/inertia-laravel:^1.0']);

        // Install NPM packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@inertiajs/vue3' => '^1.0.0',
                '@vitejs/plugin-vue' => '^4.0.0',
                'vue' => '^3.2.41',
                // Add TypeScript dependencies if needed
                ...($this->option('typescript') ? [
                    'typescript' => '^5.0.2',
                    '@types/node' => '^18.15.11',
                    'vue-tsc' => '^1.2.0',
                ] : []),
                // Add ESLint if requested
                ...($this->option('eslint') ? [
                    '@rushstack/eslint-patch' => '^1.2.0',
                    '@vue/eslint-config-prettier' => '^7.1.0',
                    '@vue/eslint-config-typescript' => '^11.0.3',
                    '@vue/tsconfig' => '^0.4.0',
                    'eslint' => '^8.36.0',
                    'eslint-plugin-vue' => '^9.10.0',
                    'prettier' => '^2.8.7',
                ] : []),
            ] + $packages;
        });

        // Copy backend files
        $common->authBackendFiles();

        // Copy frontend files
        $common->authFrontendFiles();

        // Install middleware
        $this->installMiddleware(['HandleInertiaRequests::class'], 'web');
        $this->installMiddlewareAliases([
            'inertia' => \Inertia\Middleware\HandleInertiaRequests::class,
        ]);

        if ($this->option('ssr')) {
            $this->installInertiaVueSsrStack();
        }

        if ($this->option('dark')) {
            $this->installDarkMode();
        }

        // Install Tests...
        if (! $this->installTests()) {
            return 1;
        }

        $this->components->info('Inertia Vue scaffolding installed successfully.');

        if ($this->confirm('Would you like to install and build your NPM dependencies?', true)) {
            $this->runCommands(['npm install', 'npm run build']);

            $this->components->info('NPM packages installed successfully.');
        }

        return 0;
    }

    /**
     * Install the Inertia Vue SSR stack.
     */
    private function installInertiaVueSsrStack(): void
    {
        $this->updateNodePackages(function ($packages) {
            return [
                '@vue/server-renderer' => '^3.2.31',
                '@inertiajs/server' => '^0.1.0',
            ] + $packages;
        });

        $this->configureZiggyForSsr();
    }

    /**
     * Install dark mode support.
     */
    private function installDarkMode(): void
    {
        $this->updateNodePackages(function ($packages) {
            return [
                '@vueuse/core' => '^10.1.2',
            ] + $packages;
        });
    }
}
