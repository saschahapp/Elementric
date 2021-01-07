<?php

Elementric\Loader\EnvLoader::load();

Elementric\Container\DependencyContainer::getInstance()
    
    ->register('queryBuilder')
    ->asNewInstanceOf('Elementric\Database\Query\QueryBuilder')
    ->withDependencies(
        [
            'databaseConnection',
            'queryGrammar'
        ]
    )
    
    ->register('databaseConnection')
    ->asNewInstanceOf('Elementric\Database\PDO\Connection')
    ->withDependencies(
        [
            'databaseConnector'
        ]
    )

    ->register('queryGrammar')
    ->asNewInstanceOf('Elementric\Database\Query\QueryGrammar')
    
    ->register('databaseConnector')
    ->asNewInstanceOf('Elementric\Database\Connectors\DatabaseConnector')
    
;