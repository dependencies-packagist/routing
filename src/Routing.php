<?php

namespace Annotation\Routing;

use Closure;
use Illuminate\Routing\Route;

class Routing
{
    public function directories(array $directories = []): Closure
    {
        return function (array $directories): void {
            app(RouteRegistrar::class)->directories($directories);
        };
    }

    public function gateway(string $endpoint = 'gateway.do', Closure|null $action = null, Closure|null $version = null): Closure
    {
        return function (string $endpoint = 'gateway.do', ?Closure $action = null, ?Closure $version = null): Route {
            return app(GateWayRouteRegistrar::class)->gateway($endpoint, $action, $version);
        };
    }
}
