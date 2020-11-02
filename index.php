<?php

session_start();

if ($_SERVER['REQUEST_URI'] == "/")
{
   $_SERVER['REQUEST_URI'] = "/login";
}

require (__DIR__."/src/vendor/autoload.php");
require (__DIR__."/src/Dispatch.php");
require (__DIR__."/src/Login.php");
require (__DIR__."/src/Controller.php");

function filterHtmlTags($value)
{
   return htmlentities($value, ENT_QUOTES, "UTF-8");
}

$controller = new Controller();

$pathinfo = $_SERVER["REQUEST_URI"];

$routes = [
   '/logout' => [
      'method' => 'logout'
   ],
   '/login' => [
      'method' => 'login'
   ],
   '/dispatch' => [
      'method' => 'dispatch'
   ]
];

if (isset($routes[$pathinfo]))
{
    $route = $routes[$pathinfo];
    $method = $route['method'];
    echo $controller->$method();
}