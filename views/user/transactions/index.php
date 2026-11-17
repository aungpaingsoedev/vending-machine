<?php
$pageTitle = 'Purchase History';
require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Purchase History</h1>
        <a href="/products" class="btn btn-outline-secondary">Back to products</a>
    </div>

    <?php if ($transactions === []): ?>
        <div class="alert alert-info">You have no purchase history.</div>
    <?php else: ?>
        <div class="table-responsive bg-white rounded-3 shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit price</th>
                    <th>Total price</th>
                    <th>Purchased at</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= (int) $transaction['id'] ?></td>
                        <td><?= htmlspecialchars($transaction['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $transaction['quantity'] ?></td>
                        <td>$<?= number_format((float) $transaction['unit_price'], 3) ?></td>
                        <td>$<?= number_format((float) $transaction['total_price'], 3) ?></td>
                        <td><?= htmlspecialchars($transaction['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

</main>
<?php require dirname(__DIR__, 2) . '/layouts/footer.php'; ?>
