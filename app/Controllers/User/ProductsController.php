<?php

declare (strict_types = 1);

namespace App\Controllers\User;

use App\Attributes\Route;
use App\Contracts\ProductRepositoryInterface;
use App\Contracts\RedirectorInterface;
use App\Middleware\AuthMiddleware;
use App\Services\PurchaseService;
use InvalidArgumentException;
use RuntimeException;

final class ProductsController 
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PurchaseService $purchaseService,
        private readonly AuthMiddleware $authMiddleware,
        private readonly RedirectorInterface $redirector
    ) {
    }

    public function index(): void
    {
        $this->authMiddleware->handle();

        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $perPage   = 2;
        $sort      = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';

        $totalProducts = $this->productRepository->count();
        $totalPages    = max(1, (int) ceil($totalProducts / $perPage));
        $page          = min($page, $totalPages);

        $products = $this->productRepository->findPaginated(
            $page,
            $perPage,
            $sort,
            $direction
        );

        require dirname(__DIR__, 3) . '/views/user/products/index.php';
    }

    #[Route(
        '/products/{id}/purchase',
        methods: ['GET']
    )]
    public function showPurchase(string $id): void
    {
        $this->authMiddleware->handle();

        if (! ctype_digit($id) || (int) $id < 1) {
            $this->notFound();
        }

        $product = $this->productRepository->findById((int) $id);

        if ($product === null) {
            $this->notFound();
        }

        $success     = $_SESSION['purchase_success'] ?? null;
        $error       = $_SESSION['purchase_error'] ?? null;
        $oldQuantity = $_SESSION['purchase_quantity'] ?? 1;

        unset(
            $_SESSION['purchase_success'],
            $_SESSION['purchase_error'],
            $_SESSION['purchase_quantity']
        );

        require dirname(__DIR__, 3)
            . '/views/user/products/purchase.php';
    }

    #[Route(
        '/products/{id}/purchase',
        methods: ['POST']
    )]
    public function purchase(string $id): void
    {
        $this->authMiddleware->handle();

        if (! ctype_digit($id) || (int) $id < 1) {
            $this->notFound();
        }

        $productId = (int) $id;
        $quantity  = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($quantity === false) {
            $_SESSION['purchase_error'] =
                'Purchase quantity must be at least one.';
            $_SESSION['purchase_quantity'] =
            $_POST['quantity'] ?? 1;

            $this->redirect("/products/{$productId}/purchase");
        }

        try {
            $result = $this->purchaseService->purchase(
                (int) $_SESSION['user']['id'],
                $productId,
                $quantity
            );

            $_SESSION['purchase_success'] = sprintf(
                'Purchased %d %s. Total: $%s',
                $result['quantity'],
                $result['product'],
                $result['total_price']
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $_SESSION['purchase_error']    = $exception->getMessage();
            $_SESSION['purchase_quantity'] = $quantity;
        }

        $this->redirect("/products/{$productId}/purchase");
    }

    private function notFound(): never
    {
        http_response_code(404);

        echo '404 - Product not found.';
        exit;
    }

    private function redirect(string $path): void
    {
        $this->redirector->redirect($path);
    }
}
