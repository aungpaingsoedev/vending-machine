<?php
$pageTitle = 'Manage Products';
require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Manage Products</h1>
            <p class="text-secondary mb-0">Logged in as <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/transactions" class="btn btn-outline-secondary">Transactions</a>
            <a href="/admin/products/create" class="btn btn-primary">Add product</a>
        </div>
    </div>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive bg-white rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Available quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo (int) $product['id'] ?></td>
                    <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>$<?php echo number_format((float) $product['price'], 3) ?></td>
                    <td><?php echo (int) $product['quantity_available'] ?></td>
                    <td>
                        <div class="d-flex gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="/admin/products/<?php echo (int) $product['id'] ?>/edit">
                            Edit
                        </a>

                        <form class="d-inline"
                            action="/admin/products/<?php echo (int) $product['id'] ?>/delete"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                        >
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <form action="/logout" method="POST" class="mt-4">
        <button type="submit" class="btn btn-outline-secondary">Logout</button>
    </form>
</main>
<?php require dirname(__DIR__, 2) . '/layouts/footer.php'; ?>
