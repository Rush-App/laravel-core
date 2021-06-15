<?php

namespace RushApp\Core;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use RushApp\Core\Console\Commands\Install;
use RushApp\Core\Middleware\CheckUserAction;

class CoreServiceProvider extends ServiceProvider
{
    private array $commands = [
        Install::class,
    ];

    private array $middlewareAliases = [
        'core.check-user-action' => CheckUserAction::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadConfigs();
        $this->loadTranslationsFrom(realpath(__DIR__.'/../resources/lang'), 'core');
        $this->registerMigrations();
        $this->publishFiles();
        $this->aliasMiddleware();
    }

    private function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    private function loadConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rushapp_core.php', 'rushapp_core');
    }

    private function publishFiles(): void
    {
        $configFiles = [__DIR__.'/../config' => config_path()];
        $languageFiles = [__DIR__.'/../resources/lang' => resource_path('lang/vendor/core')];

        $minimum = array_merge(
            $configFiles,
            $languageFiles
        );

        $this->publishes($configFiles, 'config');
        $this->publishes($languageFiles, 'lang');
        $this->publishes($minimum, 'minimum');
    }

    private function aliasMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        foreach ($this->middlewareAliases as $alias => $middleware) {
            $router->aliasMiddleware($alias, $middleware);
        }
    }
}
