<?php

declare (strict_types = 1);

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\TransactionRepositoryInterface;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class PurchaseService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function purchase(
        int $userId,
        int $productId,
        int $quantity
    ): array {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Purchase quantity must be at least one.'
            );
        }

        $this->connection->beginTransaction();

        try {
            $product = $this->productRepository
                ->findByIdForUpdate($productId);

            if ($product === null) {
                throw new RuntimeException(
                    'Product not found.'
                );
            }

            if (
                (int) $product['quantity_available']
                < $quantity
            ) {
                throw new RuntimeException(
                    'Insufficient product stock.'
                );
            }

            $stockUpdated = $this->productRepository
                ->decreaseStock($productId, $quantity);

            if (! $stockUpdated) {
                throw new RuntimeException(
                    'Insufficient product stock.'
                );
            }

            $unitPrice = (string) $product['price'];

            $totalPrice = number_format(
                (float) $unitPrice * $quantity,
                3,
                '.',
                ''
            );

            $transactionId = $this->transactionRepository
                ->create(
                    $userId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $totalPrice
                );

            $this->connection->commit();

            return [
                'transaction_id' => $transactionId,
                'product'        => $product['name'],
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'total_price'    => $totalPrice,
            ];
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
