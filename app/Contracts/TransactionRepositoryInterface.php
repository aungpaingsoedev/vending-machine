<?php

declare(strict_types=1);

namespace App\Contracts;

interface TransactionRepositoryInterface
{
    public function create(
        int $userId,
        int $productId,
        int $quantity,
        string $unitPrice,
        string $totalPrice
    ): int;

    public function findByUserId(int $userId): array;

    public function findAll(): array;
}
