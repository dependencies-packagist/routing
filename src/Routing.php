<?php

namespace Annotation\Routing;

use Closure;

class Routing
{
    public function directories(): Closure
    {
        return function (array $directories): void {
            app(RouteRegistrar::class)->directories($directories);
        };
    }

    public function gateway(): Closure
    {
        return function (string $endpoint = 'gateway.do', Closure $action = null, Closure $version = null): void {
            app(GateWayRouteRegistrar::class)->gateway($endpoint, $action, $version);
        };
    }
}
