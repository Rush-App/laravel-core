<?php

namespace RushApp\Core;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use RushApp\Core\Console\Commands\Install;
use RushApp\Core\Middleware\CheckUserAction;
use RushApp\Core\Middleware\SetLanguage;

class CoreServiceProvider extends ServiceProvider
{
    private array $commands = [
        Install::class,
    ];

    private array $middlewareAliases = [
        'core.check-user-action' => CheckUserAction::class,
        'core.set-language' => SetLanguage::class,
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
        $this->macroWhereLike();
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

    //Search in multiple columns in model
    private function macroWhereLike(): void
    {
        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);

                            $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                            });
                          },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });

            return $this;
        });
    }
}
