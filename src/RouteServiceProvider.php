<?php

namespace Annotation\Routing;

use Annotation\Routing\Facades\Route;
use Illuminate\Routing\Router;
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
        $this->app->bind(RouteRegistrar::class, fn() => tap(
            new RouteRegistrar(app('router')),
            fn(RouteRegistrar $routeRegistrar) => $routeRegistrar
                ->useBasePath(app()->path())
                ->useRootNamespace(app()->getNamespace())
                ->useMiddleware($this->getRouteMiddlewares())
                ->useWithoutMiddleware($this->getRouteExcludedMiddlewares())
        ));

        $this->app->singleton(GateWayRouteRegistrar::class, function () {
            return new GateWayRouteRegistrar(app('router'));
        });

        Router::mixin(new Routing);

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
        return config('routing.directories', []);
    }

    private function getRouteMiddlewares(): array
    {
        return config('routing.middleware', []);
    }

    private function getRouteExcludedMiddlewares(): array
    {
        return config('routing.excluded_middleware', []);
    }
}
