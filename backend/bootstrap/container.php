<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Controllers\AuthController;

$container = new Container();

$container->set(PDO::class, function () {

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['APP_DB_HOST'] ?? throw new RuntimeException('DB host not set'),
        $_ENV['APP_DB_PORT'] ?? 5432,
        $_ENV['APP_DB_NAME'] ?? throw new RuntimeException('DB name not set')
    );

    return new PDO(
        $dsn,
        $_ENV['APP_DB_USER'] ?? throw new RuntimeException('DB user not set'),
        $_ENV['APP_DB_PASSWORD'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
});

$container->set(UserRepository::class, function ($c) {
    return new UserRepository($c->get(PDO::class));
});

$container->set(AuthService::class, function ($c) {
    return new AuthService(
        $c->get(PDO::class),
        $c->get(UserRepository::class),
        $_ENV['JWT_SECRET'] ?? throw new RuntimeException('JWT_SECRET not set')
    );
});

$container->set(AuthController::class, function ($c) {
    return new AuthController(
        $c->get(AuthService::class)
    );
});

AppFactory::setContainer($container);

return $container;