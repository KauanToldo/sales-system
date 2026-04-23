<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;

$container = new Container();

AppFactory::setContainer($container);

return $container;