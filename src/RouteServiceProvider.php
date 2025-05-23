<?php

namespace Annotation\Routing;

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
        if (!$this->shouldRegisterRoutes()) {
            return;
        }
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
