<?php

use Slim\Factory\AppFactory;

(require __DIR__ . '/container.php');

$app = AppFactory::create();

(require __DIR__ . '/middleware.php')($app);
(require __DIR__ . '/routes.php')($app);

return $app;