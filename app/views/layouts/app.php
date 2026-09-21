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
<body class="layout-app">
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="global-nav">
    <div class="nav-inner">
        <a class="nav-brand" href="<?= e(url('dashboard')) ?>" aria-label="<?= e(config('app.name')) ?> dashboard">
            <?= brand_logo(34) ?>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>
        <?php include APP_PATH . '/views/partials/topnav.php'; ?>
        <div class="nav-actions">
            <?php if (Auth::check()): $u = Auth::user(); ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('shop')) ?>" target="_blank" rel="noopener">Shop ↗</a>
                <span class="nav-user" aria-label="Signed in as <?= e($u['name'] ?? 'User') ?>, <?= e($u['role']) ?>">
                    <span class="avatar"><?= e(strtoupper(substr($u['name'] ?? 'U', 0, 1))) ?></span>
                    <span class="name"><?= e(explode(' ', $u['name'])[0]) ?>
                        <span class="badge badge-<?= $u['role'] === 'admin' ? 'blue' : 'gray' ?>"><?= e($u['role']) ?></span>
                    </span>
                </span>
                <form method="post" action="<?= e(url('logout')) ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-signout">Sign out</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</header>
<button class="nav-backdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

<main id="main-content" class="content<?= !empty($wide) ? ' wide' : '' ?>" tabindex="-1">
    <div class="flash-region" aria-live="polite" aria-atomic="true">
        <?php include APP_PATH . '/views/partials/flash.php'; ?>
    </div>
    <?= $content ?>
</main>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
