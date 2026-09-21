<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0b5fcc">
<title><?= e($title ?? config('app.name')) ?></title>
<link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="layout-auth">
<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-wrap" tabindex="-1">
    <div class="auth-card">
        <div class="auth-brand">
            <?= brand_logo(64) ?>
            <p><?= e(config('app.tagline')) ?></p>
        </div>
        <?php include APP_PATH . '/views/partials/flash.php'; ?>
        <?= $content ?>
    </div>
</main>
</body>
</html>
