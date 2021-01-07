<?php

Elementric\Container\DependencyContainer::getInstance()
    ->register('routeController')
    ->asNewInstanceOf('Elementric\Routing\RoutingController');