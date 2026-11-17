<?php

declare(strict_types=1);

use App\Controllers\User\HomeController;
use App\Controllers\User\ProductsController;
use App\Controllers\User\TransactionsController;
use App\Core\Router;

/** @var Router $router */
/** @var App\Repositories\ProductRepository $productRepository */
/** @var App\Services\PurchaseService $purchaseService */
/** @var App\Middleware\AuthMiddleware $authMiddleware */
/** @var App\Repositories\TransactionRepository $transactionRepository */

$homeController = new HomeController();
$productsController = new ProductsController(
    $productRepository,
    $purchaseService,
    $authMiddleware,
    $redirector
);
$transactionsController = new TransactionsController(
    $transactionRepository,
    $authMiddleware
);

$router->get('/', [$homeController, 'index']);
$router->get('/products', [$productsController, 'index']);
$router->registerController($productsController);
$router->get(
    '/transactions',
    [$transactionsController, 'index']
);
