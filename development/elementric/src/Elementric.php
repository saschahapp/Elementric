<?php

use Elementric\Loader\PathLoader;

abstract class Elementric
{
    public static $initialized = false;
    public static $inits = [];

    public static function init(callable $callable) : void 
    {
        self::$inits[] = $callable;
    }

    public static function autoload(string $class) : void 
    {
        $class = explode('\\', $class);
        $class = end($class);

        if (!file_exists(PathLoader::$paths[$class])) 
        {
            return;
        }

        require PathLoader::$paths[$class];

        if (self::$inits && !self::$initialized) 
        {
            self::$initialized = true;
            
            foreach (self::$inits as $init) 
            {
                call_user_func($init);
            }
        }

        return;
    }

    public static function registerAutoload(callable $callable = null) : void 
    {
        if (null !== $callable) 
        {
            self::$inits[] = $callable;
        }

        spl_autoload_register(['Elementric', 'autoload']);

        return;
    }
}