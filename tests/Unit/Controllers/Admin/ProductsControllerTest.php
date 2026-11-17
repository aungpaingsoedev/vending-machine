<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Admin;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\RedirectorInterface;
use App\Controllers\Admin\ProductsController;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_POST = [];
        $_SESSION = [
            'user' => [
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];

        parent::tearDown();
    }

    public function testIndexDisplaysProducts(): void
    {
        [$controller, $repository] = $this->makeController();

        $repository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 100, 'name', 'asc')
            ->willReturn([$this->product()]);

        ob_start();
        $controller->index();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Manage Products', $output);
        $this->assertStringContainsString('Coke', $output);
        $this->assertStringContainsString('$3.990', $output);
    }

    public function testIndexHandlesEmptyProductList(): void
    {
        [$controller, $repository] = $this->makeController();
        $repository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 100, 'name', 'asc')
            ->willReturn([]);

        ob_start();
        $controller->index();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Manage Products', $output);
        $this->assertStringNotContainsString('Coke', $output);
    }

    public function testStoreCreatesValidProduct(): void
    {
        [$controller, $repository, $redirector] = $this->makeController(true);
        $_POST = ['name' => 'Water', 'price' => '0.500', 'quantity' => '12'];

        $repository->expects($this->once())
            ->method('create')
            ->with('Water', '0.500', 12)
            ->willReturn(4);
        $redirector->expects($this->once())
            ->method('redirect')
            ->with('/admin/products');

        $controller->store();

        $this->assertSame(
            'Product created successfully.',
            $_SESSION['success_message']
        );
    }

    public function testStoreRejectsInvalidProductInput(): void
    {
        [$controller, $repository, $redirector] = $this->makeController(true);
        $_POST = ['name' => '', 'price' => '0', 'quantity' => '-1'];

        $repository->expects($this->never())->method('create');
        $redirector->expects($this->once())
            ->method('redirect')
            ->with('/admin/products/create');

        $controller->store();

        $this->assertArrayHasKey('name', $_SESSION['product_errors']);
        $this->assertArrayHasKey('price', $_SESSION['product_errors']);
        $this->assertArrayHasKey('quantity', $_SESSION['product_errors']);
    }

    public function testUpdateChangesExistingProduct(): void
    {
        [$controller, $repository, $redirector] = $this->makeController(true);
        $_POST = ['name' => 'Coke Zero', 'price' => '4.500', 'quantity' => '20'];

        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($this->product());
        $repository->expects($this->once())
            ->method('update')
            ->with(1, 'Coke Zero', '4.500', 20);
        $redirector->expects($this->once())
            ->method('redirect')
            ->with('/admin/products');

        $controller->update('1');

        $this->assertSame(
            'Product updated successfully.',
            $_SESSION['success_message']
        );
    }

    public function testEditThrowsNotFoundForMissingProduct(): void
    {
        [$controller, $repository] = $this->makeController();
        $repository->expects($this->once())
            ->method('findById')->with(999)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $controller->edit('999');
    }

    public function testDestroyDeletesExistingProduct(): void
    {
        [$controller, $repository, $redirector] = $this->makeController(true);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($this->product());
        $repository->expects($this->once())->method('delete')->with(1);
        $redirector->expects($this->once())
            ->method('redirect')
            ->with('/admin/products');

        $controller->destroy('1');

        $this->assertSame(
            'Product deleted successfully.',
            $_SESSION['success_message']
        );
    }

    public function testIndexRejectsNonAdminUser(): void
    {
        $_SESSION['user']['role'] = 'user';
        [$controller, $repository] = $this->makeController();
        $repository->expects($this->never())->method('findPaginated');

        $this->expectException(ForbiddenException::class);
        $controller->index();
    }

    private function makeController(bool $expectRedirect = false): array
    {
        /** @var ProductRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $redirector = $expectRedirect
            ? $this->createMock(RedirectorInterface::class)
            : $this->createStub(RedirectorInterface::class);

        $controller = new ProductsController(
            $repository,
            new AdminMiddleware(new AuthMiddleware($redirector)),
            $redirector
        );

        return [$controller, $repository, $redirector];
    }

    private function product(): array
    {
        return [
            'id' => 1,
            'name' => 'Coke',
            'price' => '3.990',
            'quantity_available' => 10,
        ];
    }
}
