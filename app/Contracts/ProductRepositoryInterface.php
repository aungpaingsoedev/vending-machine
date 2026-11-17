<?php

declare (strict_types = 1);

namespace App\Contracts;

interface ProductRepositoryInterface
{
    public function count(): int;

    public function findPaginated(
        int $page,
        int $perPage,
        string $sort = 'name',
        string $direction = 'asc'
    ): array;

    public function findById(int $id): ?array;

    public function create(
        string $name,
        string $price,
        int $quantity
    ): int;

    public function update(
        int $id,
        string $name,
        string $price,
        int $quantity
    ): void;

    public function delete(int $id): void;

    public function findByIdForUpdate(int $id): ?array;

    public function decreaseStock(
        int $id,
        int $quantity
    ): bool;
}
