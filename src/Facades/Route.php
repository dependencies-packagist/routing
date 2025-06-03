<?php

namespace Annotation\Routing\Facades;

use Illuminate\Support\Facades\Route as Facade;

/**
 * @method static void directories(array $directories)
 * @method static void gateway(string $endpoint = 'gateway.do', callable $action = null, callable $version = null)
 *
 * @see \Annotation\Routing\Routing
 * @see \Illuminate\Routing\Router
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
