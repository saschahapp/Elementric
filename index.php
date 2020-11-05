<?php

session_start();

error_reporting(0);

if ($_SERVER['REQUEST_URI'] == "/")
{
   $_SERVER['REQUEST_URI'] = "/login";
}

require (__DIR__."/src/vendor/autoload.php");

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