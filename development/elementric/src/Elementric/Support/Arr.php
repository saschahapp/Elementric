<?php

namespace Elementric\Support;

use ArrayAccess;

abstract class Arr
{
    public static function accessible($value) : bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function add(array $array, string $key, mixed $value) : array
    {
        if (is_null(static::get($array, $key))) 
        {
            static::set($array, $key, $value);
        }

        return $array;
    }

    public static function divide(array $array) : array
    {
        return [array_keys($array), array_values($array)];
    }

    public static function dot($array, string $prepend = '') : array
    {
        $results = [];

        foreach ($array as $key => $value) 
        {
            if (is_array($value) && !empty($value)) 
            {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } 
            else 
            {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    public static function except($array, $keys) : array
    {
        static::forget($array, $keys);

        return $array;
    }

    public static function exists($array, $key) : bool
    {
        if ($array instanceof ArrayAccess) 
        {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    public static function first($array, callable $callback = null, $default = null) : mixed
    {
        if (is_null($callback)) 
        {
            if (empty($array)) 
            {
                return value($default);
            }

            foreach ($array as $item) 
            {
                return $item;
            }
        }

        foreach ($array as $key => $value) 
        {
            if ($callback($value, $key)) 
            {
                return $value;
            }
        }

        return value($default);
    }

    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) 
        {
            return empty($array) ? value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function flatten($array, $depth = INF)
    {
        $result = [];

        foreach ($array as $item)
        {

            if (!is_array($item)) 
            {
                $result[] = $item;
            } 
            else 
            {
                $values = $depth === 1
                    ? array_values($item)
                    : static::flatten($item, $depth - 1);

                foreach ($values as $value) 
                {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0)
        {
            return;
        }

        foreach ($keys as $key) 
        {
            if (static::exists($array, $key)) 
            {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            $array = &$original;

            while (count($parts) > 1) 
            {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) 
                {
                    $array = &$array[$part];
                } 
                else 
                {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) 
        {
            return value($default);
        }

        if (is_null($key)) 
        {
            return $array;
        }

        if (static::exists($array, $key)) 
        {
            return $array[$key];
        }

        if (strpos($key, '.') === false) 
        {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) 
        {
            if (static::accessible($array) && static::exists($array, $segment)) 
            {
                $array = $array[$segment];
            } 
            else 
            {
                return value($default);
            }
        }

        return $array;
    }

    public static function has($array, $keys) : bool
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) 
        {
            return false;
        }

        foreach ($keys as $key) 
        {
            $subKeyArray = $array;

            if (static::exists($array, $key)) 
            {
                continue;
            }

            foreach (explode('.', $key) as $segment) 
            {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) 
                {
                    $subKeyArray = $subKeyArray[$segment];
                } 
                else 
                {
                    return false;
                }
            }
        }

        return true;
    }

    public static function hasAny($array, $keys) : bool
    {
        if (is_null($keys)) 
        {
            return false;
        }

        $keys = (array) $keys;

        if (! $array) 
        {
            return false;
        }

        if ($keys === []) 
        {
            return false;
        }

        foreach ($keys as $key) 
        {
            if (static::has($array, $key)) 
            {
                return true;
            }
        }

        return false;
    }

    public static function isAssoc(array $array) : bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    public static function only($array, $keys) : array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    public static function pluck($array, $value, $key = null)
    {
        $results = [];

        [$value, $key] = static::explodePluckParameters($value, $key);

        foreach ($array as $item) 
        {
            $itemValue = data_get($item, $value);

            if (is_null($key)) 
            {
                $results[] = $itemValue;
            } 
            else 
            {
                $itemKey = data_get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) 
                {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    protected static function explodePluckParameters($value, $key) : array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    public static function prepend($array, $value, $key = null)
    {
        if (func_num_args() == 2) 
        {
            array_unshift($array, $value);
        } 
        else 
        {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    public static function random($array, $number = null, $preserveKeys = false)
    {
        $requested = is_null($number) ? 1 : $number;

        $count = count($array);

        if ($requested > $count) 
        {
            throw new Exception(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if (is_null($number)) 
        {
            return $array[array_rand($array)];
        }

        if ((int) $number === 0) 
        {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        if ($preserveKeys) 
        {
            foreach ((array) $keys as $key) 
            {
                $results[$key] = $array[$key];
            }
        } 
        else 
        {
            foreach ((array) $keys as $key)
            {
                $results[] = $array[$key];
            }
        }

        return $results;
    }

    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) 
        {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) 
        {
            if (count($keys) === 1) 
            {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key])) 
            {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function shuffle($array, $seed = null)
    {
        if (is_null($seed)) 
        {
            shuffle($array);
        } 
        else 
        {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }


    public static function sortRecursive($array, $options = SORT_REGULAR, $descending = false)
    {
        foreach ($array as &$value) 
        {
            if (is_array($value)) 
            {
                $value = static::sortRecursive($value, $options, $descending);
            }
        }

        if (static::isAssoc($array)) 
        {
            $descending
                    ? krsort($array, $options)
                    : ksort($array, $options);
        } 
        else 
        {
            $descending
                    ? rsort($array, $options)
                    : sort($array, $options);
        }

        return $array;
    }

    public static function query($array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function wrap($value)
    {
        if (is_null($value)) 
        {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    public static function append(array $array, string $clue = ' ') : string 
    {
        $string = ' ';

        foreach ($array as $value)
        {
            $string .= $value.$clue.' ';
        }

        return substr($string, 0, strlen($string) - (strlen($clue) + 1));
    }
}