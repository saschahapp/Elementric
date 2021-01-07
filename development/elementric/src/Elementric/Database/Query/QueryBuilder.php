<?php
declare(strict_types=1);

namespace Elementric\Database\Query;

use Elementric\Database\PDO\Connection;
use Elementric\Database\Query\QueryStatement as Statement;
use Elementric\Support\Arr;
use PDOStatement;
use Closure;
use PDO;

class QueryBuilder 
{
    private $connection;

    private $grammar;

    private $statements = [];

    private $currentStatement = null;

    private $operators = 
    [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    private $fetchMode = PDO::FETCH_BOTH;

    private $fetchModes =
    [
        PDO::FETCH_CLASS,
        PDO::FETCH_ASSOC,
        PDO::FETCH_BOTH,
        PDO::FETCH_LAZY,
        PDO::FETCH_NUM

    ];

    private $fetchClass = null;

    private $prepared = false;

    public function __construct(Connection $connection, QueryGrammar $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    public function table(string $table, string $as = null) : self
    {
        $this->statements[] = new Statement(compact('table'));
        $this->currentStatement = end($this->statements);

        if (!is_null($as))
        {
            $this->currentStatement->aliasTable($as);
        }

        return $this;
    }

    public function select(array $columns) : self 
    {
        $this->currentStatement->columns($columns);

        return $this;
    }

    public function insert(array $columns) : void
    {
        $this->currentStatement->mode('insert');
        $this->currentStatement->inserts($columns);

        $this->dispatch();
    }

    public function update(array $columns) : void 
    {
        $this->currentStatement->mode('update');
        $this->currentStatements->updates($columns);

        $this->dispatch();
    }

    public function delete() : void 
    {
        $this->currentStatement->mode('delete');

        $this->dispatch();
    }

    public function selectRaw(string $query, array $values = null) : array
    {
        $query = ltrim($query);

        if (strpos("SELECT", $query) !== 0)
        {
            throw new QueryBuilderException("The query needs to start with the \"SELECT\" Key Word");
        }

        if (!is_null($values))
        {
            $this->currentStatement->preparedArguments($values);
        }

        return $this->dispatch($query)->fetchAll($this->fetchMode);
    }

    public function insertRaw(string $query, array $values = null) : void 
    {
        $query = ltrim($query);

        if (strpos("INSERT", $query) !== 0)
        {
            throw new QueryBuilderException("The query needs to start with the \"INSERT\" Key Word");
        }

        if (!is_null($values))
        {
            $this->currentStatement->preparedArguments($values);
        }

        $this->dispatch($query);
    }

    public function updateRaw(string $query, array $values = null) : void 
    {
        $query = ltrim($query);

        if (strpos("UPDATE", $query) !== 0)
        {
            throw new QueryBuilderException("The query needs to start with the \"UPDATE\" Key Word");
        }

        if (!is_null($values))
        {
            $this->currentStatement->preparedArguments($values);
        }

        $this->dispatch($query);
    }

    public function deleteRaw(string $query, array $values = null) : void 
    {
        $query = ltrim($query);

        if (strpos("DELETE", $query) !== 0)
        {
            throw new QueryBuilderException("The query needs to start with the \"DELETE\" Key Word");
        }

        if (!is_null($values))
        {
            $this->currentStatement->preparedArguments($values);
        }

        $this->dispatch($query);
    }

    public function where($condition, $operator, $value = null, string $boolean = "AND", bool $not = false) : self
    {
        if (is_null($value))
        {
            [$value, $operator] = [$operator, '='];
        }

        if ($operator !== '=')
        {
            if (!in_array($operator, $this->operators))
            {
                $operator = '=';
            }
        }

        if ($condition instanceof self)
        {
            $this->currentStatement = reset($this->statements);
        }

        $where = $boolean.' ';
        $where .= $not ? " NOT " : null;
        $where .= $condition instanceof self ? '()' : $this->grammar->wrap($this->currentStatement, $condition);
        $where .= ' '.$operator.' ';
        $where .= $this->prepared ? '?' : $this->grammar->wrapValue((string) $value);

        $this->prepared ? $this->currentStatement->preparedArguments($values) : null;

        $this->currentStatement->wheres($where);

        return $this;
    }

    public function orWhere($condition, $operator, $value = null) : self 
    {
        return $this->where($condition, $operator, $value, "OR", false);
    }

    public function notWhere($condition, $operator, $value = null) : self
    {
        return $this->where($condition, $operator, $value, "AND", true);
    }

    public function orNotWhere($condition, $operator, $value = null) : self 
    {
        return $this->where($condition, $operator, $value, "OR", true);
    }

    public function orderBy(string $column, string $sort = 'ASC') : self
    {
        $this->currentStatement->orderBy($column);
        $this->currentStatement->orderBySort([$column => $sort]);

        return $this;
    }

    public function orderByDesc(string $column) : self
    {
        $this->orderBy($column, 'DESC');

        return $this;
    }

    public function reorder() : self
    {
        $this->currentStatement->orderBy();
        $this->currentStatement->orderBySort();

        return $this;
    }

    public function randomColumn() 
    {
        $query = "SELECT COUNT(*) FROM ".$this->grammar->wrap($this->$currentStatement, $this->currentStatement->table);
        $count = rand(1, current($this->connection->query($query)->fetch($this->fetchMode)));

        return ($stmt = $this->find($count)) === null ? $this : $stmt;
    }

    public function whereColumn() : self 
    {
        $columns = func_get_args();
        $countStrings = 0;
        $whereArray = [];

        foreach ($columns as $column)
        {
            if (is_array($column))
            {
                if (($count = count($column)) < 2)
                {
                    throw new QueryBuilderException('Each array needs at least 2 arguments. First colum and second column');
                } 
                elseif ($count > 3)
                {
                    throw new QueryBuilderException('Each array can have max 3 arguments. First column, then operator and the final columns');
                }

                if ($count === 2)
                {
                    $column1 = $column[0];
                    $column2 = $column[1];
                    $operator = '=';
                }
                else 
                {
                    $column1 = $column[0];
                    $column2 = $column[2];
                    $operator = in_array($column[1], $this->operators) ? $column[1] : '=';
                }

                $where = ' AND '.$this->grammar->wrap($this->currentStatement, $column1);
                $where .= ' '.$operator;
                $where .= ' '.$this->grammar->wrap($this->currentStatement, $column2);

                $this->currentStatement->wheres($where);
            }
            else 
            {
                if ($countStrings > 3)
                {
                    throw new QueryBuilderException('You can give the function max 3 String-Arguments. Column, Operator, Column');
                }

                $whereArray[] = $column;
                $countStrings++;
            }
        }

        if ($countStrings === 2)
        {
            $where = $this->grammar->wrap($this->currentStatement, $whereArray[0]).
            ' = '.$this->grammar->wrap($this->currentStatement, $whereArray[1]);

            $this->currentStatement->wheres($where);
        }
        elseif ($countStrings === 3)
        {
            $where = $this->grammar->wrap($this->currentStatement, $whereArray[0]).' ';
            $where .= in_array($whereArray[1], $this->operators) ? $opeator : '=';
            $where .= ' '.$this->grammar->wrap($this->currentStatement, $whereArray[1]);

            $this->currentStatement->wheres($where);
        }

        return $this;
    }

    public function whereNull(string $column, string $boolean = "AND", $not = false) : self 
    {
        $where = $boolean;
        $where .= $this->grammar->wrap($this->currentStatement, $column);
        $where .= $not ? " IS NOT NULL " : " IS NULL";

        $this->currentStatement->wheres($where);

        return $this;
    }

    public function orWhereNull(string $column) : self 
    {
        return $this->whereNull($column, "OR", false);
    }

    public function whereNotNull(string $column) : self
    {
        return $this->whereNull($column, "AND", true);
    }

    public function orWhereNotNull(string $column) : self 
    {
        return $this->whereNull($column, "OR", true);
    }

    public function whereIn(string $column, array $values, string $boolean = "AND", bool $not = false) : self 
    {
        foreach ($values as $value)
        {
            $in[] = $this->grammar->wrapValue((string) $value);
        }

        $where = $boolean;
        $where .= $this->grammar->wrap($this->currentStatement, $column);
        $where .= $not ? " NOT " : null;
        $where .= " IN (".Arr::append($in, ',').")";

        $this->currentStatement->wheres($where);

        return $this;
    }

    public function orWhereIn(string $column, array $values) : self 
    {
        return $this->whereIn($column, $values, "OR", false);
    }

    public function orWhereNotIn(string $column, array $values) : self 
    {
        return $this->whereIn($column, $values, "OR", true);
    }

    public function whereNotIn(string $column, array $values) : self 
    {
        return $this->whereIn($column, $values, "AND", true);
    }

    public function orWhereBetween(string $column, array $values) : self 
    {
        return $this->whereBetween($column, $values, "OR", false);
    }

    public function orWhereNotBetween(string $column, array $values) : self 
    {
        return $this->whereBetween($column, $values, "OR", true);
    }

    public function whereNotBetween(string $column, array $values) : self 
    {
        return $this->whereBetween($column, $values, "AND", true);
    }

    public function whereBetween(string $column, array $values, string $boolean = "AND", bool $not = false) : self 
    {
        if (count($values) !== 2)
        {   
            throw new QueryBuilerException("You need to pass for the Between-Method 2 values in the second Parameter");
        }

        $where = $boolean;
        $where .= $this->grammar->wrap($this->currentStatement, $column);
        $where .= $not ? " NOT " : null;
        $where .= " BETWEEN ".$this->grammar->wrapValue((string) $values[0]);
        $where .= " AND ".$this->grammar->wrapValue((string) $values[1]);

        $this->currentStatement->wheres($where);

        return $this;
    }

    public function truncate() : void 
    {
        $this->currentStatement->mode('delete');
        $this->currentStatement->truncate(true);

        $this->dispatch();
    }

    public function first() : array
    {
        $this->limit(1);

        return $this->dispatch()->fetch($this->fetchMode);
    }

    public function limit(int $limit) : self 
    {
        $this->currentStatement->limit($limit);

        return $this;
    } 

    public function offset(int $offset) : self
    {
        $this->currentStatement->offset($offset);

        return $this;
    }

    public function exists() : bool 
    {
        return current($this->count()) > 0 ? true : false;
    }

    public function doesntExist() : bool 
    {
        return !($this->exists());
    }

    public function distinct(string $column) : self 
    {
        $distinct =  "DISTINCT(".$this->grammar->wrap($this->currentStatement, $column).")";

        $this->currentStatement->columns($distinct);

        return $this;
    }

    public function crossJoin(string $table) : self 
    {
        return $this->join($table, null, null, null, "CROSS");
    }

    public function leftJoin(string $table, string $column, string $operator, string $column2) : self 
    {
        return $this->join($table, $column, $operator, $column2, "LEFT");
    }

    public function rightJoin(string $table, string $column, string $operator, string $column2) : self 
    {
        return $this->join($table, $column, $operator, $column2, "RIGHT");
    }

    public function join(string $table, ?string $column, ?string $operator, ?string $column2, string $joinType = "INNER") : self
    {
        if ($joinType === 'CROSS')
        {
            $this->currentStatement->joins(compact('joinType', 'table'));

            return $this;
        } 
        elseif (is_null($column) && is_null($operator))
        {
            throw new QueryBuilderException("You need to pass for the join-Method at least 3 Parameters");
        }

        if (is_null($column2))
        {
            [$column2, $operator] = [$operator, '='];
        }

        if ($operator !== '=')
        {
            if (!in_array($operator, $this->operators))
            {
                $operator = '=';
            }
        }

        $this->currentStatement->joins(compact('joinType', 'table', 'column', 'operator', 'column2'));

        return $this;
    }

    public function get() 
    {
        return is_null($stmt = $this->dispatch()) ? $this : $stmt->fetchAll($this->fetchMode);
    }

    public function find(int $id)
    {
        $this->where("id", $id);

        return is_null($stmt = $this->dispatch()) ? $this : $stmt->fetch($this->fetchMode);
    }

    public function value(string $column) 
    {
        $this->currentStatement->columns($column);

        return is_null($stmt = $this->dispatch()) ? $this : $stmt->fetch($this->fetchMode);
    }

    public function pluck()
    {
        $this->currentStatement->columns(func_get_args());

        return is_null($stmt = $this->dispatch()) ? $this : $stmt->fetchAll($this->fetchMode);
    }

    public function count() 
    {
        return $this->aggregate();
    }

    public function max(string $column)
    {
        return $this->aggregate("MAX()", $column);
    }

    public function min(string $column)
    {
        return $this->aggregate("MIN()", $column);
    }

    public function avg(string $column) 
    {
        return $this->aggregate("AVG()", $column);
    }

    public function sum(string $column) 
    {
        return $this->aggregate("SUM()", $column);
    }

    private function aggregate(string $type = "COUNT(*)", string $column = null)
    {
        $aggregate = is_null($column) ? $type 
        : str_replace('()','('.$this->grammar->wrap($this->currentStatement, $column).')', $type);

        $this->currentStatement->aggregate($aggregate);
        
        return is_null($stmt = $this->dispatch()) ? $this : $stmt->fetch($this->fetchMode);
    }
    
    public function setDefaultFetchMode(int $fetchMode, string $fetchClass = null) : self 
    {
        if (in_array($fetchMode, $this->fetchModes))
        {
            $this->fetchMode = $fetchMode;
        }
        
        if ($this->fetchMode === PDO::FETCH_CLASS)
        {
            if (!is_null($fetchClass))
            {
                $this->fetchClass = $fetchClass;
            }
            else 
            {
                throw new QueryBuilderException("If you choose the FETCH_CLASS mode, you need to set the second Parameter");
            }
        }
        
        return $this;
    }

    public function existTable() : bool
    {
        return is_array($this->getTable());
    }

    public function getTable() : array 
    {
        return $this->dispatch(sprintf("SHOW TABLES LIKE '%s'", 
        $this->currentStatement->table))->fetch($this->fetchMode);
    }

    public function existColumn(string $column) : bool
    {
        return is_array($this->getColumn($column));
    }

    public function getColumnNames() : array 
    {
        return array_map(function ($array) { return $array["Field"]; }, $this->getColumns());
    }

    public function getColumns() : array 
    {
        return $this->dispatch(sprintf("SHOW COLUMNS FROM `%s`",
        $this->currentStatement->table))->fetchAll($this->fetchMode);
    }

    public function getColumn(string $column) : array
    {
        return $stmt = $this->dispatch(sprintf("SHOW COLUMNS FROM `%s` LIKE '%s'",
        $this->currentStatement->table, $this->grammar->cleanVariable($column)))->fetch($this->fetchMode)
        ? $stmt : [];
    }

    public function setPreparedStatement(bool $status = true) : void 
    {
        $this->prepared = $status;
    }

    private function compileQuery() : string 
    {
        $querys = [];

        foreach (array_reverse($this->statements) as $statement)
        {
            $function = 'compileQuery';
            $function .= isset($statement->mode) ? ucfirst($statement->mode) : 'Select';

            $querys[] = $this->grammar->$function($statement, $querys);
        }

        return end($querys);
    }
    
    private function dispatch(string $query = null) : ?PDOStatement
    {
        if ($this->currentStatement !== reset($this->statements))
        {
            return null;
        }

        if (is_null($query))
        {
            $query = $this->compileQuery();
        }

        $this->flush();

        if ($this->prepared && !empty($arguments = $this->currentStatement->preparedArguments))
        {
            return $this->connection->prepare($query)->execute($arguments);
        }

        return $this->connection->query($query);
    }

    private function flush() : void 
    {
        $this->currentStatement = null;
        $this->statements = [];
        $this->prepared = false;
    }
}