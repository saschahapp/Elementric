<?php

namespace Elementric\Database\Schema;

use Elementric\Database\Schema\SchemaBlueprint as Blueprint;
use Elementric\Database\Schema\SchemaCommand as Command;
use Elementric\Database\Schema\SchemaColumnDefinition as ColumnDefinition;
use Elementric\Database\PDO\Connection;
use Elementric\Database\Grammar;
use Elementric\Support\Arr;

class SchemaGrammar extends Grammar
{
    private $modifiers = 
    [
        'Unsigned', 'Charset', 'Collate', 
        'VirtualAs', 'StoredAs', 'Nullable',
        'Srid', 'Default', 'Increment',
        'Comment', 'After', 'First'
    ];

    private $serials = 
    [
        'bigInteger', 'integer', 'mediumInteger',
        'smallInteger', 'tinyInteger'
    ];



    public function compileCreate(Blueprint $blueprint, Command $command, Connection $connection) : array 
    {
        $sql = $this->compileCreateTable($blueprint, $command, $connection);

        $sql = $this->compileCreatingEncoding($sql, $connection, $blueprint);

        return array_values([$this->compileCreateEngine($sql, $connection, $blueprint)]);
    }

    public function compileDrop(Blueprint $blueprint, Command $command, Connection $connection) : string
    {
        return 'drop table '.$this->wrap($blueprint, $blueprint->table);
    }

    public function compileCreateTable(Blueprint $blueprint, Command $command, Connection $connection) : string 
    {
        return trim(sprintf(' %s table %s (%s)',
        isset($blueprint->temporary) && $blueprint->temporary ? 'create temporary' : 'create',
        $this->wrap($blueprint, $blueprint->table),
        implode(', ', $this->getColumns($blueprint))));
    }

    public function getColumns(Blueprint $blueprint) : array
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column)
        {
            $sql = '`'.$this->cleanVariable($column->name).'` '.$this->getType($column);

            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    public function getType(ColumnDefinition $column) : string 
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    public function addModifiers(string $sql, Blueprint $blueprint, ColumnDefinition $column) : string 
    {
        foreach ($this->modifiers as $modifier)
        {
            if (method_exists($this, $method = "modify{$modifier}")) 
            {
                $sql .= $this->{$method}($blueprint, $column);
            }
        }
        
        return $sql;
    }

    private function compileCreatingEncoding(string $sql, Connection $connection, Blueprint $blueprint) : string 
    {
        if (isset($blueprint->charset)) 
        {
            $sql .= ' default character set '.$blueprint->charset;
        } 
        elseif (!is_null($charset = $connection->getConfig('charset'))) 
        {
            $sql .= ' default character set '.$charset;
        }

        if (isset($blueprint->collation)) 
        {
            $sql .= " collate '{$blueprint->collation}'";
        } 
        elseif (!is_null($collation = $connection->getConfig('collation'))) 
        {
            $sql .= " collate '{$collation}'";
        }

        return $sql;
    }

    private function compileCreateEngine(string $sql, Connection $connection, Blueprint $blueprint) : string
    {
        if (isset($blueprint->engine)) 
        {
            return $sql.' engine = '.$blueprint->engine;
        } 
        elseif (!is_null($engine = $connection->getConfig('engine'))) 
        {
            return $sql.' engine = '.$engine;
        }

        return $sql;
    }

    private function typeChar(ColumnDefinition $column) : string 
    {
        return "char({$column->length})";
    }

    private function typeString(ColumnDefinition $column) : string 
    {
        return "varchar({$column->length})";
    }

    private function typeBigInteger(ColumnDefinition $column) : string 
    {
        return 'bigint';
    }

    private function typeText(ColumnDefinition $column) : string 
    {
        return 'text';
    }

    private function typeInteger(ColumnDefinition $column) : string 
    {
        return 'int';
    }

    private function typeTinyInteger(ColumnDefinition $column) : string 
    {
        return 'tinyint';
    }

    private function typeBoolean(ColumnDefinition $column) : string 
    {
        return 'tinyint(1)';
    }

    private function getDateFormat() : string
    {
        return 'Y-m-d H:i:s';
    }

    private function typeDecimal(ColumnDefinition $column) : string 
    {
        return "decimal({$column->length},{$column->precision})";
    }

    private function typeFloat(ColumnDefinition $column) : string 
    {
        return "float({$column->length},{$column->precision})";
    }

    private function typeDouble(ColumnDefinition $column) : string 
    {
        return "double({$column->length},{$column->precision})";
    }

    private function typeDate(ColumnDefinition $column) : string 
    {
        return 'date';
    }

    private function typeDatetime(ColumnDefinition $column) : string 
    {
        return 'datetime';
    }

    private function typeTimestamp(ColumnDefinition $column) : string 
    {
        return 'timestamp';
    }

    private function modifyVirtualAs(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->virtualAs) && !is_null($column->virtualAs)) 
        {
            return " as ({$column->virtualAs})";
        }

        return null;
    }

    private function modifyStoredAs(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->storedAs) && !is_null($column->storedAs)) 
        {
            return " as ({$column->storedAs}) stored";
        }

        return null;
    }

    private function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->unsigned) && $column->unsigned)
        {
            return ' unsigned';
        }

        return null;
    }

    private function modifyCharset(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->charset) && !is_null($column->charset))
        {   
            return " as ({$column->storedAs}) stored";
        }

        return null;
    }

    private function modifyCollate(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->collation) && !is_null($column->collation)) 
        {
            return " collate '{$column->collation}'";
        }

        return null;
    }

    private function modifyNullable(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if ((!isset($column->virtualAs) && !isset($column->storedAs))
            || (is_null($colmn->virtualAs) && is_null($column->storedAs)))
        {
            return isset($column->nullable) && $column->nullable ? ' null' : ' not null';
        }

        if (isset($column->nullable) && $column->nullable === false) 
        {
            return ' not null';
        }

        return null;
    }

    private function modifyDefault(Blueprint $blueprint, ColumnDefinition $column) : ?string
    {
        if (isset($column->default) && !is_null($column->default)) 
        {
            return ' default '.$this->getDefaultValue($column->default);
        }

        return null;
    }

    private function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (in_array($column->type, $this->serials) && isset($column->autoIncrement) && $column->autoIncrement) 
        {
            return ' auto_increment primary key';
        }

        return null;
    }

    private function modifyFirst(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->first) && !is_null($column->first))
        {
            return 'first';
        }

        return null;
    }

    private function modifyAfter(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->after) && !is_null($column->after))
        {
            return 'after '.$this->wrap($blueprint, $column->after);
        }

        return null;
    }

    private function modifyComment(Blueprint $blueprint, ColumnDefinition $column) : ?string 
    {
        if (isset($column->comment) && !is_null($column->comment)) 
        {
            return " comment '".addslashes($column->comment)."'";
        }

        return null;
    }

    private function modifySrid(Blueprint $blueprint, ColumnDefinition $column) : ?string
    {
        if (isset($column->srid) && !is_null($column->srid) && is_int($column->srid) && $column->srid > 0) 
        {
            return ' srid '.$column->srid;
        }

        return null;
    }

    public function getFluentCommands() : array 
    {
        return [];
    }

    public function compileUnique(Blueprint $blueprint, Command $command) : string
    {
        return $this->compileKey($blueprint, $command, 'unique');
    }

    public function compileIndex(Blueprint $blueprint, Command $command) : string
    {
        return $this->compileKey($blueprint, $command, 'index');
    }

    public function compilePrimary(Blueprint $blueprint, Command $command) : string
    {
        return $this->compileKey($blueprint, $command, 'primary');
    }

    public function compileSpatialIndex(Blueprint $blueprint, Command $command) : string
    {
        return $this->compileKey($blueprint, $command, 'spartialIndex');
    }

    private function compileKey(Blueprint $blueprint, Command $command, string $type) : string
    {
        return sprintf('alter table %s add %s %s%s(%s)',
            $this->wrap($blueprint, $blueprint->table),
            $type,
            $this->wrap($command->index),
            $command->algorithm ? ' using '.$command->algorithm : '',
            implode(', ', array_map([$this, 'wrap'], (array) $command->columns))
        );
    }
}