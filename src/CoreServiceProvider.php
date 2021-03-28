<?php

namespace RushApp\Core;

use Illuminate\Support\ServiceProvider;
use RushApp\Core\Console\Commands\Install;

class CoreServiceProvider extends ServiceProvider
{
    private array $commands = [
        Install::class,
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
    }

    private function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    private function loadConfigs()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/boilerplate.php', 'boilerplate');
    }

    private function publishFiles()
    {
        $configFiles = [__DIR__.'/../config' => config_path()];
        $languageFiles = [__DIR__.'/../resources/lang' => resource_path('lang/vendor/core')];

        $this->publishes($configFiles, 'config');
        $this->publishes($languageFiles, 'lang');
    }
}
