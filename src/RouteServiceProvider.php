<?php

namespace Annotation\Routing;

use Annotation\Routing\Facades\Route;
use Annotation\Routing\Router;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/routing.php', 'routing');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/routing.php' => config_path('routing.php'),
            ], 'config');
        }

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $this->app->bind(RouteRegistrar::class, function () {
            return new RouteRegistrar(app('router'));
        });

        \Illuminate\Routing\Router::mixin(new Router);

        if (!$this->shouldRegisterRoutes()) {
            return;
        }

        Route::directories($this->getRouteDirectories());
    }

    private function shouldRegisterRoutes(): bool
    {
        if (!config('routing.enabled')) {
            return false;
        }

        if ($this->app->routesAreCached()) {
            return false;
        }

        return true;
    }

    private function getRouteDirectories(): array
    {
        return config('routing.directories');
    }
}
