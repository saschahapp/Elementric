<?php

namespace Elementric\Support\Traits;

use Closure;

trait Tappable
{
    public static function tap(mixed $value, Closure $callback) : mixed
    {
        $callback($value);

        return $value;
    }
}