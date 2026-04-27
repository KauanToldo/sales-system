<?php

use Slim\App;
use App\Controllers\CustomerController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app) {

    $app->group('/customers', function ($group) {
        $group->get('', CustomerController::class . ':index');
        $group->get('/{id}', CustomerController::class . ':show');
        $group->post('', CustomerController::class . ':store');
        $group->put('/{id}', CustomerController::class . ':update');
        $group->delete('/{id}', CustomerController::class . ':destroy');
    })->add(JwtAuthMiddleware::class);

};
