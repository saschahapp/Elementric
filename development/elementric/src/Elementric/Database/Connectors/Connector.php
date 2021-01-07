<?php

namespace Elementric\Database\Connectors;

use Exception;
use PDO;
use Throwable;

class Connector
{
    use DetectsLostConnections;

    protected $options = 
    [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function createConnection(string $dsn, array $config, array $options) : PDO
    {
        [$username, $password] = 
        [
            $config['username'] ?? null, $config['password'] ?? null
        ];

        try 
        {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        } 
        catch (Exception $e) 
        {
            return $this->tryAgainIfCausedByLostConnection($e, $dsn, $username, $password, $options);
        }
    }

    protected function createPdoConnection(string $dsn, string $username, string $password, array $options) : PDO
    {
        return new PDO($dsn, $username, $password, $options);
    }

    protected function isPersistentConnection(array $options) : bool
    {
        return isset($options[PDO::ATTR_PERSISTENT]) && $options[PDO::ATTR_PERSISTENT];
    }

    protected function tryAgainIfCausedByLostConnection(Throwable $e, string $dsn, string $username, 
                                                        string $password, array $options) : PDO
    {
        if ($this->causedByLostConnection($e)) 
        {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    public function getOptions(array $config) : array
    {
        $options = $config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }

    public function getDefaultOptions() : array
    {
        return $this->options;
    }

    public function setDefaultOptions(array $options) : void
    {
        $this->options = $options;
    }
}