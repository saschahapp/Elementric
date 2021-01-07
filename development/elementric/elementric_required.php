<?php
require(__DIR__.'/src/Elementric/Loader/PathLoader.php');
Elementric\Loader\PathLoader::load(__DIR__);

require(__DIR__.'/src/Elementric.php');
Elementric::registerAutoload(function ()
{
    require __DIR__.'/src/Elementric/dependency_maps/database_query_deps.php';
    require __DIR__.'/src/Elementric/dependency_maps/database_schema_deps.php';
    require __DIR__.'/src/Elementric/dependency_maps/routing_deps.php';
});
