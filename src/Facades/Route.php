<?php

namespace Annotation\Routing\Facades;

use Illuminate\Support\Facades\Route as Facade;

/**
 * @method static void directories(array $directories)
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
    protected static function getFacadeAccessor()
    {
        return parent::getFacadeAccessor();
    }
}
