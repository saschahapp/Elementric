<?php

Elementric\Container\DependencyContainer::getInstance()

    ->register('schemaBuilder')
    ->asNewInstanceOf('Elementric\Database\Schema\SchemaBuilder')
    ->withDependencies(
        [
            'databaseConnection',
            'schemaGrammar'
        ]
    )

    ->register('schemaGrammar')
    ->asNewInstanceOf('Elementric\Database\Schema\SchemaGrammar')
;