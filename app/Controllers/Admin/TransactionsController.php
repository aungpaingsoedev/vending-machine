<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Middleware\AdminMiddleware;
use App\Repositories\TransactionRepository;

final class TransactionsController
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly AdminMiddleware $adminMiddleware
    ) {
    }

    public function index(): void
    {
        $this->adminMiddleware->handle();

        $transactions = $this->transactionRepository->findAll();

        require dirname(__DIR__, 3)
            . '/views/admin/transactions/index.php';
    }
}
