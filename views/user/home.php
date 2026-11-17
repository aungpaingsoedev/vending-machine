<?php
$pageTitle = 'Vending Machine';
require dirname(__DIR__) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="p-5 bg-white rounded-4 shadow-sm text-center">
        <h1 class="display-5 fw-bold">Vending Machine</h1>
        <p class="lead text-secondary">Choose a product and make your purchase.</p>
        <a href="/products" class="btn btn-primary btn-lg">Browse products</a>
    </div>
</main>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
