<?php

namespace Annotation\Routing;

use Closure;

class Routing
{
    public function directories(): Closure
    {
        return fn(array $directories) => app(RouteRegistrar::class)->directories($directories);
    }
}
