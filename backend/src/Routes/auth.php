<?php

use Slim\App;
use App\Controllers\AuthController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app) {

    $app->post('/auth/register', AuthController::class . ':register');
    $app->post('/auth/login', AuthController::class . ':login');
    $app->get('/auth/me', AuthController::class . ':me')->add(JwtAuthMiddleware::class);

};