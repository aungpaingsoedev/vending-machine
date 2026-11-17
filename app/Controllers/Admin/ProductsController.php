<?php

declare (strict_types = 1);

namespace App\Controllers\Admin;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\RedirectorInterface;
use App\Exceptions\NotFoundException;
use App\Middleware\AdminMiddleware;
use PDOException;

final class ProductsController 
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly AdminMiddleware $adminMiddleware,
        private readonly RedirectorInterface $redirector
    ) {
    }

    public function index(): void
    {
        $this->adminMiddleware->handle();

        $products = $this->productRepository->findPaginated(
            page: 1,
            perPage: 100,
            sort: 'name',
            direction: 'asc'
        );

        $successMessage = $_SESSION['success_message'] ?? null;
        $errorMessage   = $_SESSION['error_message'] ?? null;

        unset(
            $_SESSION['success_message'],
            $_SESSION['error_message']
        );

        require dirname(__DIR__, 3) . '/views/admin/products/index.php';
    }

    public function create(): void
    {
        $this->adminMiddleware->handle();

        $errors = $_SESSION['product_errors'] ?? [];
        $old    = $_SESSION['product_old'] ?? [];

        unset(
            $_SESSION['product_errors'],
            $_SESSION['product_old']
        );

        require dirname(__DIR__, 3)
            . '/views/admin/products/create.php';
    }

    public function store(): void
    {
        $this->adminMiddleware->handle();

        $name  = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        );

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Product name is required.';
        }

        if (! is_numeric($price) || (float) $price <= 0) {
            $errors['price'] = 'Price must be greater than zero.';
        }

        if ($quantity === false) {
            $errors['quantity'] =
                'Quantity must be zero or greater.';
        }

        if ($errors !== []) {
            $_SESSION['product_errors'] = $errors;

            $_SESSION['product_old'] = [
                'name'     => $name,
                'price'    => $price,
                'quantity' => $_POST['quantity'] ?? '',
            ];

            $this->redirector->redirect('/admin/products/create');
            return;
        }

        $this->productRepository->create(
            $name,
            $price,
            $quantity
        );

        $_SESSION['success_message'] =
            'Product created successfully.';

        $this->redirector->redirect('/admin/products');
    }

    public function edit(string $id): void
    {
        $this->adminMiddleware->handle();

        if (! ctype_digit($id) || (int) $id < 1) {
            $this->notFound();
        }

        $product = $this->productRepository->findById(
            (int) $id
        );

        if ($product === null) {
            $this->notFound();
        }

        $errors = $_SESSION['product_errors'] ?? [];
        $old    = $_SESSION['product_old'] ?? [];

        unset(
            $_SESSION['product_errors'],
            $_SESSION['product_old']
        );

        require dirname(__DIR__, 3)
            . '/views/admin/products/edit.php';
    }

    public function update(string $id): void
    {
        $this->adminMiddleware->handle();

        if (! ctype_digit($id) || (int) $id < 1) {
            $this->notFound();
        }

        $productId = (int) $id;

        if ($this->productRepository->findById($productId) === null) {
            $this->notFound();
        }

        $name  = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        );

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Product name is required.';
        }

        if (! is_numeric($price) || (float) $price <= 0) {
            $errors['price'] =
                'Price must be greater than zero.';
        }

        if ($quantity === false) {
            $errors['quantity'] =
                'Quantity must be zero or greater.';
        }

        if ($errors !== []) {
            $_SESSION['product_errors'] = $errors;

            $_SESSION['product_old'] = [
                'name'     => $name,
                'price'    => $price,
                'quantity' => $_POST['quantity'] ?? '',
            ];

            $this->redirector->redirect(
                "/admin/products/{$productId}/edit"
            );
            return;
        }

        $this->productRepository->update(
            $productId,
            $name,
            $price,
            $quantity
        );

        $_SESSION['success_message'] =
            'Product updated successfully.';

        $this->redirector->redirect('/admin/products');
    }

    public function destroy(string $id): void
    {
        $this->adminMiddleware->handle();

        if (! ctype_digit($id) || (int) $id < 1) {
            $this->notFound();
        }

        $productId = (int) $id;

        if ($this->productRepository->findById($productId) === null) {
            $this->notFound();
        }

        try {
            $this->productRepository->delete($productId);

            $_SESSION['success_message'] =
                'Product deleted successfully.';
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $_SESSION['error_message'] =
                'This product cannot be deleted because it has transaction records.';
        }

        $this->redirector->redirect('/admin/products');
    }

    private function notFound(): never
    {
        throw new NotFoundException('404 - Product not found.');
    }

}
