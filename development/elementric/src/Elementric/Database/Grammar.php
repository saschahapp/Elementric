<?php

namespace Elementric\Database;

abstract class Grammar 
{
    public function wrap($statement, string $value = null) : string 
    {
        if (func_num_args() === 2 && is_object($statement) && isset($statement->table))
        {
            $value = $this->cleanVariable($value);

            return ($value === $statement->table)  
            ? '`'.$value.'`'
            : '`'.$statement->table.'`.`'.$value.'`';
        }
        else 
        {
            $value = is_null($value) ? $statement : $value;

            $value = $this->cleanVariable($value);

            return '`'.$value.'`';
        }
    }

    public function wrapValue(string $value) : string 
    {
        $value = $this->cleanVariable($value);

        return '\''.$value.'\'';
    }

    public function cleanVariable(string $value) : string 
    {
        $value = str_replace('\'', '', $value);
        $value = str_replace('`', '', $value);

        return $value;
    }
}