<?php

namespace Elementric\Support\Facades;

use Elementric\Container\DependencyContainer;

class Schema extends Facade
{
    protected static function getFacadeAccessor() : object
    {
        if (!isset(static::$resolvedInstance['schema']))
        {
            static::$resolvedInstance['schema'] = DependencyContainer::getInstance()->lookup('schemaBuilder');
        }

        return static::$resolvedInstance['schema'];
    }
}