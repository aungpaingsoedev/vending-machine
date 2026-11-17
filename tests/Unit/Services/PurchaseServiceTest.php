<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\TransactionRepositoryInterface;
use App\Services\PurchaseService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PurchaseServiceTest extends TestCase
{
    public function testPurchaseUpdatesStockLogsTransactionAndCommits(): void
    {
        $connection = $this->createMock(PDO::class);
        $products = $this->createMock(ProductRepositoryInterface::class);
        $transactions = $this->createMock(TransactionRepositoryInterface::class);

        $connection->expects($this->once())->method('beginTransaction')->willReturn(true);
        $products->expects($this->once())->method('findByIdForUpdate')->with(2)
            ->willReturn($this->product(quantity: 10));
        $products->expects($this->once())->method('decreaseStock')->with(2, 3)
            ->willReturn(true);
        $transactions->expects($this->once())->method('create')
            ->with(7, 2, 3, '6.885', '20.655')
            ->willReturn(42);
        $connection->expects($this->once())->method('commit')->willReturn(true);

        $result = (new PurchaseService($connection, $products, $transactions))
            ->purchase(7, 2, 3);

        $this->assertSame(42, $result['transaction_id']);
        $this->assertSame('20.655', $result['total_price']);
    }

    public function testPurchaseRollsBackWhenStockIsInsufficient(): void
    {
        $connection = $this->createMock(PDO::class);
        $products = $this->createMock(ProductRepositoryInterface::class);
        $transactions = $this->createMock(TransactionRepositoryInterface::class);

        $connection->expects($this->once())->method('beginTransaction')->willReturn(true);
        $products->expects($this->once())->method('findByIdForUpdate')->with(2)
            ->willReturn($this->product(quantity: 1));
        $products->expects($this->never())->method('decreaseStock');
        $transactions->expects($this->never())->method('create');
        $connection->expects($this->once())->method('inTransaction')->willReturn(true);
        $connection->expects($this->once())->method('rollBack')->willReturn(true);
        $connection->expects($this->never())->method('commit');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient product stock.');

        (new PurchaseService($connection, $products, $transactions))
            ->purchase(7, 2, 2);
    }

    public function testPurchaseRollsBackWhenProductDoesNotExist(): void
    {
        $connection = $this->createMock(PDO::class);
        $products = $this->createMock(ProductRepositoryInterface::class);
        $transactions = $this->createMock(TransactionRepositoryInterface::class);

        $connection->expects($this->once())->method('beginTransaction')->willReturn(true);
        $products->expects($this->once())
            ->method('findByIdForUpdate')->with(999)->willReturn(null);
        $transactions->expects($this->never())->method('create');
        $connection->expects($this->once())->method('inTransaction')->willReturn(true);
        $connection->expects($this->once())->method('rollBack')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Product not found.');

        (new PurchaseService($connection, $products, $transactions))
            ->purchase(7, 999, 1);
    }

    public function testPurchaseRejectsQuantityBelowOneBeforeTransaction(): void
    {
        $connection = $this->createMock(PDO::class);
        $products = $this->createMock(ProductRepositoryInterface::class);
        $transactions = $this->createMock(TransactionRepositoryInterface::class);

        $connection->expects($this->never())->method('beginTransaction');
        $products->expects($this->never())->method('findByIdForUpdate');
        $transactions->expects($this->never())->method('create');

        $this->expectException(InvalidArgumentException::class);

        (new PurchaseService($connection, $products, $transactions))
            ->purchase(7, 2, 0);
    }

    private function product(int $quantity): array
    {
        return [
            'id' => 2,
            'name' => 'Pepsi',
            'price' => '6.885',
            'quantity_available' => $quantity,
        ];
    }
}
