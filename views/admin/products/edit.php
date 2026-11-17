<?php
$pageTitle = 'Edit Product';
$includeValidation = true;
require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 40rem;">
    <div class="card-body p-4 p-md-5">
    <h1 class="h3 mb-4">Edit Product</h1>

    <form
        action="/admin/products/<?php echo (int) $product['id'] ?>"
        method="POST"
        class="needs-validation"
        novalidate
    >
        <div class="mb-3">
            <label for="name" class="form-label">Product name</label>

            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                maxlength="100"
                value="<?php echo htmlspecialchars(
                               $old['name'] ?? $product['name'],
                               ENT_QUOTES,
                           'UTF-8'
                       ) ?>"
                required
            >
            <div class="invalid-feedback">Product name is required.</div>

            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback d-block">
                    <?php echo htmlspecialchars(
                            $errors['name'],
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price</label>

            <input
                type="number"
                id="price"
                name="price"
                class="form-control"
                min="0.001"
                step="0.001"
                value="<?php echo htmlspecialchars(
                               $old['price'] ?? $product['price'],
                               ENT_QUOTES,
                           'UTF-8'
                       ) ?>"
                required
            >
            <div class="invalid-feedback">Enter a price greater than zero.</div>

            <?php if (isset($errors['price'])): ?>
                <div class="invalid-feedback d-block">
                    <?php echo htmlspecialchars(
                            $errors['price'],
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label for="quantity" class="form-label">Available quantity</label>

            <input
                type="number"
                id="quantity"
                name="quantity"
                class="form-control"
                min="0"
                step="1"
                value="<?php echo htmlspecialchars(
                               (string) (
                                   $old['quantity'] ?? $product['quantity_available']
                               ),
                               ENT_QUOTES,
                           'UTF-8'
                       ) ?>"
                required
            >
            <div class="invalid-feedback">Enter a whole number of zero or greater.</div>

            <?php if (isset($errors['quantity'])): ?>
                <div class="invalid-feedback d-block">
                    <?php echo htmlspecialchars(
                            $errors['quantity'],
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Update product</button>
        <a href="/admin/products" class="btn btn-link">Cancel</a>
    </form>
    </div>
    </div>
</main>
<?php require dirname(__DIR__, 2) . '/layouts/footer.php'; ?>
