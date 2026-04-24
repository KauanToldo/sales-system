<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {

    $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';

    // 🔥 1. PRE-FLIGHT (tem que vir primeiro)
    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    // 🔥 2. CORS HEADERS
    $app->add(function (Request $request, $handler) use ($frontendUrl) {
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $frontendUrl)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    });

    // 🔥 3. ERROR MIDDLEWARE
    $app->addErrorMiddleware(true, true, true);
    $app->addBodyParsingMiddleware();
};