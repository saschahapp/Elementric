<?php

namespace Elementric\Support\Facades;

use Closure;
use RuntimeException;

abstract class Facade
{
    protected static $app;

    protected static $resolvedInstance;

    public static function resolved(Closure $callback)
    {
        $accessor = static::getFacadeAccessor();

        if (static::$app->resolved($accessor) === true) 
        {
            $callback(static::getFacadeRoot());
        }

        static::$app->afterResolving($accessor, function ($service) use ($callback) 
        {
            $callback($service);
        });
    }

    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) 
        {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) 
        {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) 
        {
            return static::$resolvedInstance[$name] = static::$app[$name];
        }
    }

    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    public static function getFacadeApplication()
    {
        return static::$app;
    }

    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) 
        {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}