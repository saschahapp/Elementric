<?php

namespace Elementric\Support;

abstract class Str 
{
    public static function contains(string $haystack, array $needles) : bool
    {
        foreach ($needles as $needle) 
        {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) 
            {
                return true;
            }
        }

        return false;
    }
}