<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {

    $app->get('/health', function (Request $request, Response $response) {

        $payload = json_encode([
            'status' => 'ok',
            'service' => 'backend',
            'timestamp' => date(DATE_ATOM),
        ]);

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    });

};