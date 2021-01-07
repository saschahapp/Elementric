<?php
declare(strict_types=1);

namespace Elementric\Database\Query;

use Elementric\Support\Arr;
use Elementric\Database\Query\QueryStatement as Statement;
use Elementric\Database\Grammar;

class QueryGrammar extends Grammar
{
    public function compileQueryUpdate(Statement $statement, array $preQueries = null) : string 
    {
        $values = [];

        foreach ($statement->updates as $key => $value)
        {
            $values[] = $this->wrap($statement, $key)." = ".$this->wrapValue((string) $value);
        }

        $query = sprintf("UPDATE %s SET %s ",
        $this->wrap($statement, $statement->table),
        Arr::append($values, ','));

        if (isset($statement->wheres))
        {
            $query .= Arr::append((array) $statment->wheres);
        }

        return $query;
    }

    public function compileQueryInsert(Statement $statement, array $preQueries = null) : string 
    {
        $columns = [];
        $values = [];

        foreach ($statement->inserts as $key => $value)
        {
            $columns[] = $this->wrap($statement, $key);
            $values[] = $this->wrapValue((string) $value);
        }

        return sprintf("INSERT INTO %s (%s) VALUES (%s)",
        $this->wrap($statement, $statement->table),
        Arr::append($columns, ','),
        Arr::append($values, ','));
    }

    public function compileQueryDelete(Statement $statement, array $preQueries = null) : string 
    {
        if ($statement->truncate)
        {
            return sprintf("DELETE FROM %s; ALTER TABLE %s AUTO_INCREMENT = 1",
            $this->wrap($statement, $statement->table),
            $this->wrap($statement, $statement->table));
        }

        $query = "DELETE FROM ".$this->wrap($statement, $statement->table);

        if (isset($statment->wheres))
        {
            $query .= Arr::append((array) $statement->wheres);
        }

        return $query;
    }

    public function compileQuerySelect(Statement $statement, array $preQueries = null) : string 
    {
        if (isset($statement->aggregate) && isset($statement->columns))
        {
            throw new QueryBuilderException("You can not use the SELECT statement with aggregate and none aggregate Functions");
        }

        if (isset($statement->aggregate))
        {
            $query = $this->aggregate($statement);
        } 
        elseif (isset($statement->columns))
        {
            $query = $this->columns($statement);
        } 
        else 
        {
            $query = "SELECT * FROM ".$this->wrap($statement, $statement->table);
        }

        isset($statement->aliasTable) ? $query .= " as `".$statement->aliasTable."` " : null;

        isset($statement->joins) ? $query .= $this->join($statement) : null;

        isset($statement->wheres) ? $query .= $this->where($statement, $preQueries) : null;

        isset($statement->orderBy) ? $query .= $this->orderBy($statement) : null;

        if (isset($statement->limit) || isset($statement->offset)) 
        {
            $query .= $this->limit($statement);
        }

        return $query;
    }

    private function orderBy(Statement $statement) : string 
    {
        if (is_array($statement->orderBy))
        {
            $value = ' ORDER BY ';

            foreach ($statement->orderBy as $orderBy)
            {
                $value .= $this->wrap($statement, $orderBy);
                $value .= ' '.$statement->orderBySort[$orderBy].', ';
            }

            return substr($value, 0, strlen($value) - 2);
        }
        else 
        {
            return sprintf(" ORDER BY %s %s",
            $this->wrap($statement, $statement->orderBy),
            $this->orderBySort);
        }
    }

    private function limit(Statement $statement) : string 
    {
        $value = " LIMIT ";
        $value .= empty($statement->offset) ? null : $statement->offset.', ';
        $value .= empty($statement->limit) ? '1 ' : $statement->limit.' ';

        return $value;
    }

    private function where(Statement $statement, array $preQueries) : string 
    {
        $value = " WHERE ".Arr::append((array) $statement->wheres);
        $value = preg_replace('/AND|OR/', '', $value, 1);

        if (($count = substr_count($value, "()")) !== 0)
        {
            if ($count !== count($preQueries))
            {
                throw new QueryBuilderException('You habe more SubQueries created than you need for the main query');
            }

            foreach (array_reverse($preQueries) as $preQuery)
            {
                $value = preg_replace('/\(\)/', ' ('.$preQuery.') ', $value, 1);
            }
        }

        return $value;
    }

    private function join(Statement $statement) : string 
    {
        $join = $statement->joins;

        return count($join) === 5 
        ? sprintf(" %s JOIN `%s` ON `%s`.`%s` %s %s",
        $this->cleanVariable($join['joinType']), $this->cleanVariable($join['table']),
        $this->cleanVariable($join['table']), $this->cleanVariable($join['column']),
        $this->cleanVariable($join['operator'], $this->wrap($statement, $join['column2'])))
        : sprinft(" %s JOIN `%s`",
        $this->cleanVariable($join['joinType'], $this->cleanVariable($join['table'])));
    }

    private function aggregate(Statement $statement) : string 
    {
        return sprintf("SELECT %s FROM %s",
        Arr::append((array) $statement->aggregate, ','),
        $this->wrap($statement, $statement->table));
    }

    private function columns(Statement $statement) : string 
    {
        $values = [];

        if (!is_array($statement->columns))
        {
            $columns[] = $statement->columns;
        }
        else 
        {
            $columns = $statement->columns;
        }

        foreach ($columns as $column)
        {
            if (strpos(ltrim($column), "DISTINCT") === 0)
            {
                $values[] = $column;
                continue;
            }

            $values[] = $this->wrap($statement, $column);
        }

        return sprintf("SELECT %s FROM %s",
        Arr::append($values, ','),
        $this->wrap($statement, $statement->table));
    }
}