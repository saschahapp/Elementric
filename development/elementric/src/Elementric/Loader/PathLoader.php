<?php

namespace Elementric\Loader;

abstract class PathLoader 
{
    public static $paths = [];

    public static function load(string $path = __DIR__) : void 
    {
        if (is_dir($path))
        {
            $handle = opendir($path);

            while (($path2 = readdir($handle)) !== false)
            {
                if (!preg_match('/^[a-z[A-Z]|.env/', $path2))
                {
                    continue;
                }

                if (is_dir(($value = $path.'/'.$path2)))
                {
                    self::load($value);
                }
                else 
                {
                    $key = str_replace('.php', null, $path2);

                    if (array_key_exists($key, self::$paths))
                    {
                        require(__DIR__.'/LoaderException.php');

                        throw new LoaderException("You can not name your file the same as a other file. File-Name: ".$key);
                    }

                    self::$paths[$key] = $value;
                }
            }
            
            closedir($handle);
            clearstatcache();
        }
    }
}