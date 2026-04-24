<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\AuthController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app, $container) {

    $app->get('/health', function (Request $request, Response $response) {

        $payload = json_encode([
            'status' => 'ok',
            'service' => 'backend',
            'timestamp' => date(DATE_ATOM),
        ]);

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/db-test', function ($request, $response) use ($container) {

        $pdo = $container->get(PDO::class);

        $stmt = $pdo->query('SELECT 1');

        $response->getBody()->write(json_encode($stmt->fetch()));

        return $response->withHeader('Content-Type', 'application/json');
    });

    (require __DIR__ . '/../src/Routes/auth.php')($app);
};