<?php

namespace Elementric\Database\Schema;

use Closure;
use Elementric\Database\PDO\Connection;
use Elementric\Database\Schema\SchemaBlueprint as Blueprint;
use Elementric\Database\Schema\SchemaGrammar as Grammar;
use Elementric\Support\Traits\Tappable;

class SchemaBuilder 
{
    private $connection;

    private $grammar;

    public static $defaultStringLength = 255;

    public static $defaultCharLength = 30;
    
    public function __construct(Connection $connection, Grammar $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    public function create(string $table , Closure $callback) : void
    {
        $this->build(Tappable::tap($this->createBlueprint($table), function ($blueprint) use ($callback) 
        {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    public function drop(string $table) : void 
    {
        $this->build(Tappable::tap($this->createBlueprint($table), function ($blueprint) 
        {
            $blueprint->drop();
        }));
    }

    public function build(Blueprint $blueprint) : void 
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    public function createBlueprint(string $table, Closure $callback = null) : Blueprint
    {
        return new Blueprint($table, $callback);
    }
}