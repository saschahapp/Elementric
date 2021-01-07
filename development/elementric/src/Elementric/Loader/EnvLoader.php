<?php

namespace Elementric\Loader;

abstract class EnvLoader 
{
    public static function load() : void 
    {
        if (file_exists(PathLoader::$paths['.env']))
        {
            $envs = file(PathLoader::$paths['.env']);
        
            foreach ($envs as $env)
            {
                if (!empty($env) && preg_match('/^[a-z[A-Z]/', $env))
                {
                    $env = trim($env);
                    $env = explode("=", $env);
                    $_ENV[$env[0]] = $env[1];
                }
            }
        } 
        else 
        {
            throw new LoaderException("the .env file can not be found or open!");
        }

        return;
    }
}