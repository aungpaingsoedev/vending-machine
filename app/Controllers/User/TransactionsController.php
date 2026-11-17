<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Middleware\AuthMiddleware;
use App\Repositories\TransactionRepository;

final class TransactionsController
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly AuthMiddleware $authMiddleware
    ) {
    }

    public function index(): void
    {
        $this->authMiddleware->handle();

        $transactions = $this->transactionRepository
            ->findByUserId((int) $_SESSION['user']['id']);

        require dirname(__DIR__, 3)
            . '/views/user/transactions/index.php';
    }
}
