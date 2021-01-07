<?php
declare(strict_types=1);

namespace Elementric\Database\PDO;

use Elementric\Database\Connectors\DatabaseConnector;
use Elementric\Loader\PathLoader;
use PDO;
use PDOException;
use PDOStatement;

class Connection
{
    private $connector;

    private $connection;

    private $stmt;

    private $config;

    public function __construct(DatabaseConnector $connector, array $config = null)
    {
        is_null($config) ? $this->config = include(PathLoader::$paths['database.config'])
                         : $this->config = $config;

        $this->connector = $connector;
        $this->connection = $connector->connect($this->config);
        $this->setEmulatePrepared();
    }

    public function exec(string $statement) : int
    {
        try 
        {
            $result = $this->connection->exec($statement);

            assert($result !== false);

            return $result;
        } 
        catch (PDOException $exception) 
        {
            throw new PDOException($exception);
        }
    }

    public function prepare(string $sql) : self
    {
        try 
        {
            $this->stmt = $this->connection->prepare($sql);
        } 
        catch (PDOException $exception) 
        {
            throw new DatabaseConnectionException($exception);
        }

        return $this;
    }

    public function query(string $sql) : PDOStatement
    {
        try 
        {
            $stmt = $this->connection->query($sql);

            assert($stmt instanceof PDOStatement);

            return $stmt;
        } 
        catch (PDOException $exception) 
        {
            throw $exception;
        }
    }

    public function execute() : PDOStatement 
    {
        if ($this->stmt instanceof PDOStatement)
        {
            try 
            {
                return $stmt->execute();
            }
            catch (PDOException $exception)
            {
                throw new DatabaseConnectionException($exception);
            }
        }
        else 
        {
            throw new DatabaseConnectionException("You need to use the prepare method first");
        }
    }

    public function statement(string $statement) : void 
    {
        try 
        {
            $this->connection->prepare($statement)->execute();
        }
        catch (PDOException $exception)
        {
            throw $exception;
        }
    }

    public function lastInsertId($name = null)
    {
        try 
        {
            if ($name === null) 
            {
                return $this->connection->lastInsertId();
            }

            return $this->connection->lastInsertId($name);
        } 
        catch (PDOException $exception) 
        {
            throw new DatabaseConnectionException($exception);
        }
    }


    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    public function getServerVersion() : string
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getWrappedConnection() : PDO
    {
        return $this->connection;
    }

    public function setEmulatePrepared(bool $bool = false) : void
    {
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $bool);
    }

    public function getConfig(string $key) : ?string 
    {
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}