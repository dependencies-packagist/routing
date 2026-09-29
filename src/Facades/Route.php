<?php

namespace Annotation\Routing\Facades;

use Closure;
use Annotation\Routing\Routing;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as Facade;

/**
 * @method static void directories(array $directories = [])
 * @method static \Illuminate\Routing\Route gateway(string $endpoint = 'gateway.do', Closure|null $action = null, Closure|null $version = null)
 *
 * @mixin Routing
 * @see Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return parent::getFacadeAccessor();
    }
}
