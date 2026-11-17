<?php
$pageTitle = 'Purchase Product';
$includeValidation = true;
require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 36rem;">
    <div class="card-body p-4 p-md-5">
    <h1 class="h3 mb-4">Purchase Product</h1>

    <?php if ($success !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <dl class="row mb-4">
        <dt class="col-sm-5">Product</dt>
        <dd class="col-sm-7"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></dd>

        <dt class="col-sm-5">Price</dt>
        <dd class="col-sm-7">$<?= number_format((float) $product['price'], 3) ?></dd>

        <dt class="col-sm-5">Available quantity</dt>
        <dd class="col-sm-7"><?= (int) $product['quantity_available'] ?></dd>
    </dl>

    <?php if ((int) $product['quantity_available'] > 0): ?>
        <form
            action="/products/<?= (int) $product['id'] ?>/purchase"
            method="POST"
            class="needs-validation"
            novalidate
        >
            <div class="mb-3">
                <label for="quantity" class="form-label">Purchase quantity</label>
                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    class="form-control"
                    min="1"
                    max="<?= (int) $product['quantity_available'] ?>"
                    step="1"
                    value="<?= (int) $oldQuantity ?>"
                    required
                >
                <div class="invalid-feedback">
                    Enter a quantity between 1 and <?= (int) $product['quantity_available'] ?>.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Purchase</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">This product is out of stock.</div>
    <?php endif; ?>

    <a href="/products" class="btn btn-link px-0 mt-3">Back to products</a>
    </div>
    </div>
</main>
<?php require dirname(__DIR__, 2) . '/layouts/footer.php'; ?>
