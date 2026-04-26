<?php

use Slim\App;
use App\Controllers\ProductController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app) {

    $app->group('/products', function ($group) {
        $group->get('', ProductController::class . ':index');
        $group->get('/{id}', ProductController::class . ':show');
        $group->post('', ProductController::class . ':store');
        $group->put('/{id}', ProductController::class . ':update');
        $group->delete('/{id}', ProductController::class . ':destroy');
    })->add(JwtAuthMiddleware::class);

};
