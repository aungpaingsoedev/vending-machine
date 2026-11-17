<?php
$pageTitle = 'Products';
require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Products</h1>
        <a href="/transactions" class="btn btn-outline-secondary">Purchase history</a>
    </div>

    <nav class="btn-group flex-wrap mb-4" aria-label="Product sorting">
        <a class="btn btn-outline-primary" href="/products?sort=name&amp;direction=asc">Name: A-Z</a>
        <a class="btn btn-outline-primary" href="/products?sort=name&amp;direction=desc">Name: Z-A</a>
        <a class="btn btn-outline-primary" href="/products?sort=price&amp;direction=asc">Price: Low to high</a>
        <a class="btn btn-outline-primary" href="/products?sort=price&amp;direction=desc">Price: High to low</a>
    </nav>

    <?php if ($products === []): ?>
        <div class="alert alert-info">No products are available.</div>
    <?php else: ?>
        <div class="table-responsive bg-white rounded-3 shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Available quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>$<?= number_format((float) $product['price'], 3) ?></td>
                        <td><?= (int) $product['quantity_available'] ?></td>
                        <td>
    <?php if ((int) $product['quantity_available'] > 0): ?>
        <a class="btn btn-primary btn-sm" href="/products/<?= (int) $product['id'] ?>/purchase">
            Purchase
        </a>
    <?php else: ?>
        <span class="badge text-bg-secondary">Out of stock</span>
    <?php endif; ?>
</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Product pages">
            <ul class="pagination">
            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars('/products?' . http_build_query([
                    'page' => $pageNumber,
                    'sort' => $sort,
                    'direction' => $direction,
                ]), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $pageNumber ?>
                </a>
                </li>
            <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</main>
<?php require dirname(__DIR__, 2) . '/layouts/footer.php'; ?>
