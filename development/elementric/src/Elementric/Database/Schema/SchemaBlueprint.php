<?php
declare(strict_types=1);

namespace Elementric\Database\Schema;

use Closure;
use Elementric\Support\Fluent;
use Elementric\Database\PDO\Connection;
use Elementric\Database\Schema\SchemaColumnDefinition as ColumnDefinition;
use Elementric\Database\Schema\SchemaBuilder as Builder;
use Elementric\Database\Schema\SchemaCommand as Command;
use Elementric\Database\Schema\SchemaGrammar as Grammar;

class SchemaBlueprint 
{   
    public $table;

    public $columns = [];

    public $commands = [];

    public function __construct(string $table, Closure $callback = null)
    {
        $this->table = $table;

        if (!is_null($callback))
        {
            $callback($this);
        }
    }

    public function build(Connection $connection, Grammar $grammar) : void
    {
        foreach ($this->toSql($connection, $grammar) as $statement)
        {
            var_dump($statement);
            $connection->statement($statement);
        }
    }

    private function toSql(Connection $connection, Grammar $grammar) : array
    {
        $statements = [];

        $this->addImpliedCommands($grammar);

        foreach ($this->commands as $command) 
        {
            $method = 'compile'.ucfirst($command->name);

            if (method_exists($grammar, $method))
            {
                if (!is_null($sql = $grammar->$method($this, $command, $connection)))
                {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    private function addImpliedCommands(Grammar $grammar) : void
    {
        if (count($this->getAddedColumns()) > 0 && !$this->creating())
        {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (count($this->getChangedColumns()) > 0 && !$this->creating())
        {
            array_unshift($this->commands, $this->createCommand('change'));
        }

        $this->addFluentIndexes();

        $this->addFluentCommands($grammar);
    }

    private function addFluentIndexes() : void
    {
        foreach ($this->columns as $column)
        {
            foreach (['primary', 'unique', 'index', 'spatialIndex'] as $index)
            {
                if (isset($column->{$index}) && ($column->{$index} === true))
                {
                    $this->{$index}($column->name);
                    $column->{$index} = false;

                    continue 2;
                }
                elseif (isset($column->{$index}))
                {
                    $this->{$index}($column->name, $column->{$index});
                    $column->{$index} = false;

                    continue 2;
                }
            }
        }
    }

    private function addFluentCommands(Grammar $grammar) : void 
    {
        foreach ($this->columns as $column)
        {
            foreach ($grammar->getFluentCommands() as $commandName)
            {
                $attributeName = lcfirst($commandName);

                if (!isset($column->{$attributeName}))
                {
                    continue;
                }

                $value = $column->{$attributeName};

                $this->addCommand($commandName, compact('value', 'column'));
            }   
        }
    }

    private function addColumn(string $type, string $name, array $parameters) : ColumnDefinition
    {
        $this->columns[] = $column = new ColumnDefinition(array_merge(compact('type', 'name'), $parameters));

        return $column;
    }

    private function addCommand(string $name, array $parameters = []) : Command
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    private function createCommand(string $name, array $parameters = []) : Command
    {
        return new Command(array_merge(compact('name'), $parameters));
    }

    private function primary($columns, ?array $name = null, string $algorithm = null) : Command 
    {
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    private function unique($columns, ?array $name = null, string $algorithm = null) : Command
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    private function index($columns, ?array $name = null, string $algorithm = null) : Command
    {
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    public function spatialIndex($columns, ?array $name = null) : Command
    {
        return $this->indexCommand('spatialIndex', $columns, $name);
    }

    private function indexCommand(string $type, $columns, ?array $index, ?string $algorithm = null) : Command
    {
        $columns = (array) $columns;

        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand($type, compact('index', 'columns', 'algorithm'));
    }

    private function createIndexName(string $type, ?array $columns)  : string
    {
        $index = strtolower($this->table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    public function drop() : Command 
    {
        return $this->addCommand('drop');
    }

    public function create() : Command 
    {
        return $this->addCommand('create');
    }

    public function dropIfExists() : Command 
    {
        return $this->addCommand('dropIfExists');
    }

    public function creating() : bool 
    {
        return true;
    }

    public function getAddedColumns() : array
    {
        return array_filter($this->columns, function ($column)
        {
            return !isset($column->change) ? true : !$column->change;
        });
    }

    public function getChangedColumns() : array
    {
        return array_filter($this->columns, function ($column)
        {
            return !isset($column->change) ? false : (bool) $column->change;
        });
    }

    public function id(string $column = 'id') : ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    public function bigIncrements(string $column) : ColumnDefinition
    {
        return $this->unsignedBigInteger($column, true);
    }

    public function increments(string $column) : ColumnDefintion 
    {
        return $this->unsignedInteger($column, true);
    }

    public function tinyIncrements(string $column) : ColumnDefinition 
    {
        return $this->unsignedTiny($column, true);
    }

    public function unsignedBigInteger(string $column, bool $autoIncrement = false) : ColumnDefinition
    {
        return $this->bigInterger($column, $autoIncrement, true);
    }

    public function unsignedInteger(string $column, bool $autoIncrement = false) : ColumnDefinition
    {
        return $this->integer($column, $autoIncrement, true);
    }

    public function unsignedTiny(string $column, bool $autoIncrement = false) : ColumnDefinition 
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    public function bigInterger(string $column, bool $autoIncrement = false, bool $unsigned = false) : ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false) : ColumnDefinition
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false) : ColumnDefinition
    {
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    public function string(string $column, int $length = null) : ColumnDefinition
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('string', $column, compact('length'));
    }

    public function text(string $column) : ColumnDefinition 
    {
        return $this->addColumn('text', $column);
    }

    public function timestamp(string $column, int $precision = 0) : ColumnDefintion 
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    public function char(string $column, int $length = null) : ColumnDefinition 
    {
        $length = $length ?: Builder::$defaultCharLength;

        return $this->addColumn('char', $column, compact('length'));
    }

    public function decimal(string $column, int $length = 5, int $precision = 2) : ColumnDefinition 
    {
        return $this->addColumn('decimal', $column, compact('length', 'precision'));
    }

    public function float(string $column, int $length = 5, int $precision = 2) : ColumnDefinition 
    {
        return $this->addColumn('float', $column, compact('length', 'precision'));
    }

    public function double(string $column, int $length = 5, int $precision = 2) : ColumnDefinition 
    {
        return $this->addColumn('double', $column, compact('length', 'precision'));
    }

    public function date(string $column) : ColumnDefinition 
    {
        return $this->addColumn('date', $column);
    }

    public function datetime(string $column) : ColumnDefinition 
    {
        return $this->addColumn('datetime', $column);
    }

    public function time(string $column) : ColumnDefinition 
    {
        return $this->addColumn('time', $column);
    }

    public function boolean(string $column) : ColumnDefinition 
    {
        return $this->addColumn('boolean', $column);
    }
}