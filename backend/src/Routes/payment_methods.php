<?php

use Slim\App;
use App\Controllers\PaymentMethodController;
use App\Middleware\JwtAuthMiddleware;

return function (App $app) {

    $app->group('/payment-methods', function ($group) {
        $group->get('', PaymentMethodController::class . ':index');
        $group->get('/{id}', PaymentMethodController::class . ':show');
        $group->post('', PaymentMethodController::class . ':store');
        $group->put('/{id}', PaymentMethodController::class . ':update');
        $group->delete('/{id}', PaymentMethodController::class . ':destroy');
    })->add(JwtAuthMiddleware::class);

};
