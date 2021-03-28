<?php

namespace RushApp\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
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
}
