<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0b5fcc">
<title><?= e($title ?? config('app.name')) ?></title>
<?php if (!empty($ogTitle)): ?>
<meta property="og:title" content="<?= e($ogTitle) ?>">
<?php endif; ?>
<?php if (!empty($ogDescription)): ?>
<meta property="og:description" content="<?= e($ogDescription) ?>">
<meta name="description" content="<?= e($ogDescription) ?>">
<?php endif; ?>
<?php if (!empty($ogImage)): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<?php if (!empty($ogUrl)): ?>
<meta property="og:url" content="<?= e($ogUrl) ?>">
<link rel="canonical" href="<?= e($ogUrl) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= !empty($product) ? 'product' : 'website' ?>">
<meta property="og:site_name" content="<?= e(Setting::get('shop_name', config('app.name'))) ?>">
<link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/shop.css?v=' . filemtime(PUBLIC_PATH . '/assets/css/shop.css'))) ?>">
</head>
<body class="layout-shop">
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php
$shopName = Setting::get('shop_name', config('app.name'));
$navCats = array_values(array_filter(Category::all(), fn ($c) => (int) $c['is_active'] === 1));
$navCats = array_slice($navCats, 0, 5);
$current = Router::currentPath();
$isHomeHero = ($current === 'shop') && !empty($slides ?? []);
$checkoutEnabled = $checkoutEnabled ?? is_online_checkout_enabled();
$whatsappEnabled = $whatsappEnabled ?? is_whatsapp_ordering_enabled();
$waNumber = whatsapp_number();
$waDisplay = $waNumber ? whatsapp_display_number($waNumber) : '';
?>
<header class="global-nav shop-nav<?= $isHomeHero ? ' nav-over-hero' : '' ?>">
    <div class="nav-inner">
        <a class="nav-brand" href="<?= e(url('shop')) ?>" aria-label="<?= e($shopName) ?> home">
            <?= brand_logo(34, false) ?>
            <?= brand_logo(34, true) ?>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="shop-navigation" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>
        <nav id="shop-navigation" class="nav-links" aria-label="Shop navigation">
            <a class="<?= $current === 'shop' ? 'active' : '' ?>" href="<?= e(url('shop')) ?>">Home</a>
            <a class="<?= $current === 'shop/products' ? 'active' : '' ?>" href="<?= e(url('shop/products')) ?>">All products</a>
            <?php foreach ($navCats as $c): ?>
                <a href="<?= e(url('shop/products?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
            <?php if (!$checkoutEnabled): ?>
                <a href="<?= e(url('shop/products?sort=newest')) ?>">New Arrivals</a>
            <?php endif; ?>
        </nav>
        <div class="nav-actions">
            <?php if ($checkoutEnabled): ?>
                <a class="btn btn-primary btn-sm cart-link" href="<?= e(url('shop/cart')) ?>" aria-label="Open shopping cart">Cart <span id="cart-count" class="cart-count" aria-live="polite">0</span></a>
            <?php else: ?>
                <?php if ($whatsappEnabled && $waNumber !== ''): ?>
                    <a class="btn btn-primary btn-sm whatsapp-nav-link" href="<?= e('https://wa.me/' . $waNumber . '?text=' . rawurlencode('Hello ' . $shopName . ', I would like to know more about your products.')) ?>" target="_blank" rel="noopener" aria-label="Contact us on WhatsApp">
                        <span aria-hidden="true" class="wa-icon">💬</span> WhatsApp
                    </a>
                <?php elseif (trim((string) Setting::get('shop_phone', '')) !== ''): ?>
                    <a class="btn btn-outline btn-sm" href="tel:<?= e(preg_replace('/\s+/', '', Setting::get('shop_phone', ''))) ?>">Contact</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>
<button class="nav-backdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

<main id="main-content" class="shop-main" tabindex="-1">
    <div class="flash-region" aria-live="polite" aria-atomic="true">
        <?php include APP_PATH . '/views/partials/flash.php'; ?>
    </div>
    <?= $content ?>
</main>

<footer class="shop-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="nav-brand"><?= brand_logo(34) ?></div>
            <p><?= e(Setting::get('shop_tagline', 'Technology with local service and support.')) ?></p>
            <?php if ($whatsappEnabled && $waNumber !== ''): ?>
                <p style="margin-top:12px"><a class="btn btn-outline btn-sm" href="<?= e('https://wa.me/' . $waNumber) ?>" target="_blank" rel="noopener">💬 Chat on WhatsApp</a></p>
            <?php endif; ?>
        </div>
        <div class="footer-column">
            <strong>Shop</strong>
            <a href="<?= e(url('shop/products')) ?>">All products</a>
            <?php if ($checkoutEnabled): ?>
                <a href="<?= e(url('shop/cart')) ?>">Your cart</a>
                <a href="<?= e(url('shop/track')) ?>">Track an order</a>
            <?php else: ?>
                <a href="<?= e(url('shop/products')) ?>">Browse catalogue</a>
                <span>Catalogue + WhatsApp ordering</span>
            <?php endif; ?>
        </div>
        <div class="footer-column"><strong>Visit or contact</strong><span><?= e(Setting::get('shop_address', 'Nairobi, Kenya')) ?></span><?php if (Setting::get('shop_phone', '') !== ''): ?><a href="tel:<?= e(preg_replace('/\s+/', '', Setting::get('shop_phone', ''))) ?>"><?= e(Setting::get('shop_phone', '')) ?></a><?php endif; ?><?php if (Setting::get('shop_email', '') !== ''): ?><a href="mailto:<?= e(Setting::get('shop_email', '')) ?>"><?= e(Setting::get('shop_email', '')) ?></a><?php endif; ?><?php if ($waDisplay !== ''): ?><a href="<?= e('https://wa.me/' . $waNumber) ?>" target="_blank" rel="noopener">WhatsApp: <?= e($waDisplay) ?></a><?php endif; ?><span>Mon–Sat · 8:30 AM–6:00 PM</span></div>
        <div class="footer-column"><strong>Customer care</strong><span>VAT-inclusive online prices</span><span>Warranty recorded on receipts</span><?php if ($checkoutEnabled): ?><span>Returns handled with your order number</span><?php else: ?><span>Enquire on WhatsApp for availability</span><?php endif; ?></div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> <?= e($shopName) ?></span>
            <span><?= $checkoutEnabled ? 'Secure local checkout · Kenya' : 'Product catalogue · WhatsApp ordering · Kenya' ?></span>
        </div>
    </div>
</footer>

<script>
window.KC_SHOP_CONFIG = {
    checkoutEnabled: <?= $checkoutEnabled ? 'true' : 'false' ?>,
    whatsappEnabled: <?= $whatsappEnabled ? 'true' : 'false' ?>,
    whatsappNumber: <?= json_encode($waNumber) ?>,
    whatsappDisplay: <?= json_encode($waDisplay) ?>,
    shopName: <?= json_encode($shopName) ?>,
    baseUrl: <?= json_encode(url('')) ?>,
    csrf: <?= json_encode(Csrf::token()) ?>,
    whatsappEnquiryUrl: <?= json_encode(url('shop/whatsapp-enquiry')) ?>,
    currency: <?= json_encode(config('app.currency', 'KSh')) ?>
};
</script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<script src="<?= e(url('assets/js/shop.js?v=' . filemtime(PUBLIC_PATH . '/assets/js/shop.js'))) ?>"></script>
</body>
</html>
