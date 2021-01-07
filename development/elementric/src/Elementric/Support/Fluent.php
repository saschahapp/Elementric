<?php
declare(strict_types=1);

namespace Elementric\Support;

use ArrayAccess;

abstract class Fluent implements ArrayAccess
{
    private $attributes;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value)
        {
            $this->attributes[$key] = $value;
        }
    }
    
    public function get(string $key, mixed $default = null) : mixed 
    {
        if (array_key_exists($key, $this->attributes))
        {
            return $this->attributes[$key];
        }

        return value($default);
    }

    public function getAttributes() : array 
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset) : bool 
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset) : mixed 
    {
        return is_array($this->attributes[$offset]) && count($this->attributes[$offset]) === 1
        ? current($this->attributes[$offset])
        : $this->attributes[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value) : void 
    {
        is_array($this->attributes[$offset])
        ? $this->attributes[$offset] = array_merge($this->attributes[$offset], (array) $value)
        : $this->attributes[$offset] = $value;
    }

    public function offsetUnset(mixed $offset) : void 
    {
        unset($this->attributes[$offset]);
    }

    public function __call(string $method, array $parameters)
    {
        if (count($parameters) > 0)
        {
            if (current($parameters) === true)
            {
                $this->attributes[$method] = true;
            }
            else 
            {
                isset($this->attributes[$method]) && is_array($this->attributes[$method])
                ? $this->attributes[$method] = array_merge($this->attributes[$method], (array) $parameters[0])
                : $this->attributes[$method] = (array) $parameters[0];
            }
        }
        else 
        {
            $this->attributes[$method] = [];
        }

        return $this;
    }

    public function __get(string $key) 
    {
        return $this->offsetGet($key);
    }

    public function __set(string $key, mixed $value)
    {
        $this->offsetSet($key, $value);
    }

    public function __isset(string $key)
    {
        return $this->offsetExists($key);
    }

    public function __unset(string $key)
    {
        $this->offsetUnset($key);
    }
}