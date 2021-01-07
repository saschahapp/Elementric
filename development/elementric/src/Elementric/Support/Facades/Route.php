<?php

namespace Elementric\Support\Facades;

use Elementric\Container\DependencyContainer;

class Route extends Facade 
{
    protected static function getFacadeAccessor() : object
    {
        if (!isset(static::$resolvedInstance['route']))
        {
            static::$resolvedInstance['route'] = DependencyContainer::getInstance()->lookup('routeController');
        }

        return static::$resolvedInstance['route'];
    }
}