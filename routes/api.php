<?php

declare(strict_types=1);

use App\Controllers\Api\AuthController as ApiAuthController;
use App\Controllers\Api\ProductsController as ApiProductsController;
use App\Middleware\ApiAuthMiddleware;
use App\Services\JwtService;

$jwt = new JwtService(
    $_ENV['JWT_SECRET']
        ?? 'local-development-jwt-secret-change-before-deploying'
);
$apiAuth = new ApiAuthMiddleware($jwt);
$apiAuthController = new ApiAuthController($userRepository, $jwt);
$apiProductsController = new ApiProductsController(
    $productRepository,
    $purchaseService,
    $apiAuth
);

$router->post('/api/login', [$apiAuthController, 'login']);
$router->get('/api/products', [$apiProductsController, 'index']);
$router->get('/api/products/{id}', [$apiProductsController, 'show']);
$router->post('/api/products', [$apiProductsController, 'store']);
$router->put('/api/products/{id}', [$apiProductsController, 'update']);
$router->delete('/api/products/{id}', [$apiProductsController, 'destroy']);
$router->post('/api/products/{id}/purchase', [$apiProductsController, 'purchase']);
