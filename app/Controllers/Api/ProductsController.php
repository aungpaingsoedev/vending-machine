<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\NotFoundException;
use App\Middleware\ApiAuthMiddleware;
use App\Repositories\ProductRepository;
use App\Services\PurchaseService;
use InvalidArgumentException;

final class ProductsController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly PurchaseService $purchases,
        private readonly ApiAuthMiddleware $auth
    ) {
    }

    public function index(): void
    {
        $this->auth->authenticate();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 10)));
        $this->json([
            'data' => $this->products->findPaginated(
                $page,
                $perPage,
                (string) ($_GET['sort'] ?? 'name'),
                (string) ($_GET['direction'] ?? 'asc')
            ),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $this->products->count(),
        ]);
    }

    public function show(string $id): void
    {
        $this->auth->authenticate();
        $product = $this->find($id);
        $this->json(['data' => $product]);
    }

    public function store(): void
    {
        $this->auth->requireAdmin();
        [$name, $price, $quantity] = $this->validatedInput();
        $id = $this->products->create($name, $price, $quantity);
        $this->json(['data' => $this->products->findById($id)], 201);
    }

    public function update(string $id): void
    {
        $this->auth->requireAdmin();
        $product = $this->find($id);
        [$name, $price, $quantity] = $this->validatedInput();
        $this->products->update((int) $product['id'], $name, $price, $quantity);
        $this->json(['data' => $this->products->findById((int) $product['id'])]);
    }

    public function destroy(string $id): void
    {
        $this->auth->requireAdmin();
        $product = $this->find($id);
        $this->products->delete((int) $product['id']);
        http_response_code(204);
    }

    public function purchase(string $id): void
    {
        $user = $this->auth->authenticate();
        $product = $this->find($id);
        $input = $this->input();
        $quantity = filter_var(
            $input['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($quantity === false) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        $this->json(['data' => $this->purchases->purchase(
            (int) $user['sub'],
            (int) $product['id'],
            $quantity
        )], 201);
    }

    private function find(string $id): array
    {
        if (! ctype_digit($id) || (int) $id < 1) {
            throw new NotFoundException('Product not found.');
        }
        $product = $this->products->findById((int) $id);
        if ($product === null) {
            throw new NotFoundException('Product not found.');
        }
        return $product;
    }

    private function validatedInput(): array
    {
        $input = $this->input();
        $name = trim((string) ($input['name'] ?? ''));
        $price = trim((string) ($input['price'] ?? ''));
        $quantity = filter_var(
            $input['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        if ($name === '' || ! is_numeric($price) || (float) $price <= 0 || $quantity === false) {
            throw new InvalidArgumentException('Invalid product data.');
        }
        return [$name, $price, $quantity];
    }

    private function input(): array
    {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }
}
