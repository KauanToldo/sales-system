<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use App\Repositories\UserRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PaymentMethodRepository;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\CustomerService;
use App\Services\PaymentMethodService;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\CustomerController;
use App\Controllers\PaymentMethodController;

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

$container->set(ProductRepository::class, function ($c) {
    return new ProductRepository($c->get(PDO::class));
});

$container->set(CustomerRepository::class, function ($c) {
    return new CustomerRepository($c->get(PDO::class));
});

$container->set(PaymentMethodRepository::class, function ($c) {
    return new PaymentMethodRepository($c->get(PDO::class));
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

$container->set(ProductService::class, function ($c) {
    return new ProductService(
        $c->get(ProductRepository::class)
    );
});

$container->set(CustomerService::class, function ($c) {
    return new CustomerService(
        $c->get(CustomerRepository::class)
    );
});

$container->set(PaymentMethodService::class, function ($c) {
    return new PaymentMethodService(
        $c->get(PaymentMethodRepository::class)
    );
});

$container->set(ProductController::class, function ($c) {
    return new ProductController(
        $c->get(ProductService::class)
    );
});

$container->set(CustomerController::class, function ($c) {
    return new CustomerController(
        $c->get(CustomerService::class)
    );
});

$container->set(PaymentMethodController::class, function ($c) {
    return new PaymentMethodController(
        $c->get(PaymentMethodService::class)
    );
});

AppFactory::setContainer($container);

return $container;