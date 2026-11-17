<?php
$pageTitle = 'Login';
$includeValidation = true;
require dirname(__DIR__) . '/layouts/header.php';
?>
<main class="container py-5">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-md-5">
    <h1 class="h3 mb-4">Login</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                required
            >
            <div class="invalid-feedback">Enter a valid email address.</div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                required
            >
            <div class="invalid-feedback">Password is required.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    </div>
    </div>
</main>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
