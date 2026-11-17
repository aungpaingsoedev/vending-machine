<?php

declare (strict_types = 1);

use App\Controllers\Admin\ProductsController;
use App\Controllers\Admin\TransactionsController;

$productsController = new ProductsController(
    $productRepository,
    $adminMiddleware,
    $redirector
);
$transactionsController = new TransactionsController(
    $transactionRepository,
    $adminMiddleware
);

$router->get('/admin', [$productsController, 'index']);
$router->get('/admin/products', [$productsController, 'index']);

$router->get(
    '/admin/products/create',
    [$productsController, 'create']
);

$router->post(
    '/admin/products',
    [$productsController, 'store']
);

$router->get(
    '/admin/products/{id}/edit',
    [$productsController, 'edit']
);

$router->post(
    '/admin/products/{id}',
    [$productsController, 'update']
);

$router->post(
    '/admin/products/{id}/delete',
    [$productsController, 'destroy']
);
$router->get(
    '/admin/transactions',
    [$transactionsController, 'index']
);
