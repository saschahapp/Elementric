<?php

namespace Elementric\Support\Facades;

use Elementric\Container\DependencyContainer;

class DB extends Facade
{
    protected static function getFacadeAccessor() : object
    {
        if (!isset(static::$resolvedInstance['db']))
        {
            static::$resolvedInstance['db'] = DependencyContainer::getInstance()->lookup('queryBuilder');
        }

        return static::$resolvedInstance['db'];
    }
}