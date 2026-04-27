<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$container = require __DIR__ . '/container.php';

AppFactory::setContainer($container);

$app = AppFactory::create();

(require __DIR__ . '/middleware.php')($app);
(require __DIR__ . '/routes.php')($app, $container);

return $app;