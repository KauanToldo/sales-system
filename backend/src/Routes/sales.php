<?php

use Slim\App;
use App\Controllers\SaleController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app) {
    $app->group('/sales', function ($group) {
        $group->get('', SaleController::class . ':index');
        $group->get('/{id}', SaleController::class . ':show');
        $group->get('/{id}/pdf', SaleController::class . ':pdf');
        $group->post('/{id}/payments', SaleController::class . ':addPayment');
        $group->post('/{id}/finalize', SaleController::class . ':finalize');
        $group->post('', SaleController::class . ':store');
    })->add(JwtAuthMiddleware::class);
};

