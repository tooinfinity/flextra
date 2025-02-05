<?php

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use JsonException;

trait InstallModuleApi
{
    /**
     * Install the API flextra stack with Laravel Modules.
     *
     * @param $moduleName
     * @return int|null
     * @throws JsonException
     */
    protected function installApiModule($moduleName): ?int
    {
        $this->runCommands(['php artisan install:api']);
        $this->installModuleDependencies(true);

        $files = new Filesystem;

        // Controllers...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/api-module/app/Http/Controllers/Auth',
            base_path('Modules/'.$moduleName.'/app/Http/Controllers')
        );

        // Middleware...
        $files->copyDirectory(__DIR__.'/../../stubs/api-module/app/Http/Middleware', app_path('Http/Middleware'));

        $this->installMiddlewareAliases([
            'verified' => '\App\Http\Middleware\EnsureEmailIsVerified::class',
        ]);

        $this->installMiddleware([
            '\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class',
        ], 'api', 'prepend');

        // Requests...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/api-module/app/Http/Requests/Auth',
            base_path('Modules/'.$moduleName.'/app/Http/Requests')
        );

        // Providers...
        $files->copyDirectory(__DIR__.'/../../stubs/api-module/app/Providers', app_path('Providers'));

        // Routes...
        $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/api-module/routes/web.php', base_path('Modules/'.$moduleName.'/routes/web.php'));
        $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/api-module/routes/auth.php', base_path('Modules/'.$moduleName.'/routes/auth.php'));
        $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/api-module/routes/api.php', base_path('Modules/'.$moduleName.'/routes/api.php'));

        // Configuration...
        $files->copyDirectory(__DIR__.'/../../stubs/api-module/config', config_path());

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        file_put_contents(
            base_path('.env'),
            preg_replace('/APP_URL=(.*)/', 'APP_URL=http://localhost:8000'.PHP_EOL.'FRONTEND_URL=http://localhost:3000', file_get_contents(base_path('.env')))
        );

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        $files->delete(base_path('tests/Feature/Auth/PasswordConfirmationTest.php'));

        // Cleaning...
        $this->removeScaffoldingUnnecessaryForApis();

        $this->components->info('Flextra Api for  '.$moduleName.'  Module scaffolding installed successfully.');

        return 0;
    }

    /**
     * Remove any application scaffolding that isn't needed for APIs.
     *
     * @return void
     */
    protected function removeScaffoldingUnnecessaryForApis(): void
    {
        $files = new Filesystem;

        // Remove frontend related files...
        $files->delete(base_path('package.json'));
        $files->delete(base_path('vite.config.js'));
        $files->delete(base_path('tailwind.config.js'));
        $files->delete(base_path('postcss.config.js'));

        // Remove Laravel "welcome" view...
        $files->delete(resource_path('views/welcome.blade.php'));
        $files->put(resource_path('views/.gitkeep'), PHP_EOL);

        // Remove CSS and JavaScript directories...
        $files->deleteDirectory(resource_path('css'));
        $files->deleteDirectory(resource_path('js'));
    }

}
