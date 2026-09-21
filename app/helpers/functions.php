<?php
declare(strict_types=1);

/* ---------------------------------------------------------------------------
 * Environment & config helpers
 * ------------------------------------------------------------------------ */

/**
 * Parse a simple KEY=VALUE .env file into the environment.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
             || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, $default = null)
{
    $v = $_ENV[$key] ?? null;
    if ($v === null || $v === '') {
        $v = getenv($key);
    }
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

/**
 * Read the application configuration (loaded lazily, cached statically).
 * Supports dot notation, e.g. config('db.mysql.host').
 */
function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require APP_PATH . '/config/config.php';
    }
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/* ---------------------------------------------------------------------------
 * Output helpers
 * ------------------------------------------------------------------------ */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim((string) config('app.base_url', ''), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path, int $code = 302): void
{
    header('Location: ' . url($path), true, $code);
    exit;
}

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Stream a CSV download. $rows is a list of row-arrays (associative is fine —
 * the header row is just the first row). Adds a UTF-8 BOM so Excel opens
 * KSh/symbol text correctly.
 */
function csv_response(array $rows, string $filename): void
{
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename) ?: 'export.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    foreach ($rows as $row) {
        $cells = array_map(
            static fn ($value) => is_string($value) ? csv_safe_cell($value) : $value,
            array_values($row)
        );
        fputcsv($out, $cells, ',', '"', '\\');
    }
    fclose($out);
    exit;
}

/** Neutralize user-controlled text that spreadsheet apps could execute as a formula. */
function csv_safe_cell(string $value): string
{
    return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
}

function brand_mark(int $size = 32): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" fill="none" aria-hidden="true">'
        . '<rect x="3" y="6" width="42" height="28" rx="6" fill="#0071e3" fill-opacity="0.12" stroke="#0071e3" stroke-width="2.5"/>'
        . '<path d="M24 13 L15 24 L21 24 L17.5 33 L27 21.5 L20.5 21.5 Z" fill="#0071e3"/>'
        . '<path d="M17 40 h14" stroke="#0071e3" stroke-width="2.5" stroke-linecap="round"/>'
        . '</svg>';
}

/**
 * Full brand wordmark logo, sized by height (trimmed asset ratio 714:240 ≈ 2.975).
 * Pass $white = true for the knockout variant used over dark/hero backgrounds.
 */
function brand_logo(int $height = 32, bool $white = false, string $class = ''): string
{
    $height = max(1, $height);
    $width  = (int) round($height * (714 / 240));
    $file   = $white ? 'khamis-logo-white.png' : 'khamis-logo.png';
    $cls    = trim('brand-logo ' . ($white ? 'brand-logo-white' : 'brand-logo-color') . ' ' . $class);
    return '<img src="' . e(url('assets/img/' . $file)) . '" width="' . $width . '" height="' . $height
        . '" alt="" class="' . e($cls) . '" decoding="async">';
}

/**
 * Receipt logo (trimmed asset ratio 418:320 ≈ 1.306).
 * Pass $black = true for the monochrome variant used on thermal (black-only) prints.
 */
function receipt_logo(int $height = 44, bool $black = false, string $class = ''): string
{
    $height = max(1, $height);
    $width  = (int) round($height * (418 / 320));
    $file   = $black ? 'khamis-receipt-logo-black.png' : 'khamis-receipt-logo.png';
    $cls    = trim('receipt-logo ' . ($black ? 'receipt-logo-black' : 'receipt-logo-color') . ' ' . $class);
    return '<img src="' . e(url('assets/img/' . $file)) . '" width="' . $width . '" height="' . $height
        . '" alt="" class="' . e($cls) . '" decoding="async">';
}

/* ---------------------------------------------------------------------------
 * Product thumbnails (dependency-free, Apple-clean gradient tiles)
 * ------------------------------------------------------------------------ */

function thumb_palette(string $key): array
{
    $palettes = [
        ['#e8f1fc', '#cfe1fb'],  // blue
        ['#e7f8ee', '#c9f0db'],  // green
        ['#fff3e0', '#ffe0b8'],  // amber
        ['#f0ecff', '#ddd2ff'],  // violet
        ['#fdecea', '#f7cdca'],  // red
    ];
    $h = crc32($key);
    return $palettes[abs($h) % count($palettes)];
}

function product_monogram(string $name): string
{
    $m = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $w) {
        if ($w !== '') {
            $m .= strtoupper(substr($w, 0, 1));
        }
    }
    return $m !== '' ? $m : 'KC';
}

function product_thumb(array $product, int $size = 300): string
{
    // Real uploaded image (primary) when available.
    if (!empty($product['image'])) {
        return '<img src="' . e(url('uploads/p/' . rawurlencode($product['image']))) . '"'
            . ' width="' . $size . '" height="' . $size . '"'
            . ' alt="' . e($product['name'] ?? '') . '" loading="lazy"'
            . ' style="object-fit:cover;border-radius:14px;display:block">';
    }

    static $uid = 0;
    $uid++;
    [$c1, $c2] = thumb_palette((string) ($product['name'] ?? 'x'));
    $mono = product_monogram((string) ($product['name'] ?? ''));
    $cat  = strtoupper((string) ($product['category_name'] ?? ''));
    $fs   = round($size * 0.24);
    $cs   = round($size * 0.055);
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg" role="img">'
        . '<defs><linearGradient id="tg' . $uid . '" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '"/></linearGradient></defs>'
        . '<rect width="' . $size . '" height="' . $size . '" fill="url(#tg' . $uid . ')"/>'
        . '<text x="50%" y="53%" font-family="system-ui, -apple-system, Segoe UI, sans-serif" font-size="' . $fs . '" font-weight="700" fill="#1d1d1f" text-anchor="middle" dominant-baseline="middle" letter-spacing="2">' . e($mono) . '</text>'
        . ($cat !== '' ? '<text x="50%" y="' . round($size * 0.82) . '" font-family="system-ui, sans-serif" font-size="' . $cs . '" font-weight="600" fill="#6e6e73" text-anchor="middle" letter-spacing="3">' . e($cat) . '</text>' : '')
        . '</svg>';
}

/* ---------------------------------------------------------------------------
 * Flash messages & form state
 * ------------------------------------------------------------------------ */

function flash(string $key, $value = null)
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $v;
}

function flash_has(string $key): bool
{
    return isset($_SESSION['_flash'][$key]);
}

function set_old(array $data): void
{
    $_SESSION['_old_input'] = $data;
}

function old(string $key, string $default = ''): string
{
    $v = $_SESSION['_old_input'][$key] ?? null;
    return e($v ?? $default);
}

/* ---------------------------------------------------------------------------
 * CSRF helpers
 * ------------------------------------------------------------------------ */

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Csrf::token()) . '">';
}

/* ---------------------------------------------------------------------------
 * Money & VAT helpers  (prices are stored EXCLUSIVE of VAT)
 * ------------------------------------------------------------------------ */

function vat_rate(): float
{
    // Prefer the live setting (editable in Settings) over the config default.
    $fromSetting = Setting::get('vat_rate', (string) config('app.vat_rate', 16));
    return is_numeric($fromSetting) ? (float) $fromSetting : (float) config('app.vat_rate', 16);
}

function money($amount): string
{
    $amount = (float) $amount;
    return (string) config('app.currency', 'KSh') . ' ' . number_format($amount, 2, '.', ',');
}

function vat_on($net): float
{
    return round((float) $net * vat_rate() / 100, 2);
}

function gross_of($net): float
{
    return round((float) $net + vat_on($net), 2);
}

/* ---------------------------------------------------------------------------
 * Auth helpers
 * ------------------------------------------------------------------------ */

function current_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

/* ---------------------------------------------------------------------------
 * Misc
 * ------------------------------------------------------------------------ */

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-');
}

/** Build a query string from the current $_GET with overrides (null removes a key). */
function query_string(array $overrides = []): string
{
    $p = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($p[$k]);
        } else {
            $p[$k] = $v;
        }
    }
    $out = http_build_query($p);
    return $out !== '' ? '?' . $out : '';
}

/** Human label + badge class for a sale/return status. */
function status_badge(string $status): array
{
    return match ($status) {
        'completed' => ['Completed', 'green'],
        'pending'   => ['Pending', 'orange'],
        'cancelled' => ['Cancelled', 'red'],
        'offline'   => ['Offline', 'blue'],
        'approved'  => ['Approved', 'green'],
        'rejected'  => ['Rejected', 'red'],
        default     => [ucfirst($status), 'gray'],
    };
}

function db_ready(): bool
{
    try {
        Database::pdo()->query('SELECT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/* ---------------------------------------------------------------------------
 * Hero image upload, optimization and streaming
 * ------------------------------------------------------------------------ */

const HERO_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const HERO_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
const HERO_IMAGE_MAX_WIDTH = 2560;
const HERO_IMAGE_QUALITY = 82;

/**
 * Validate an uploaded hero image. Returns '' on success or an error string.
 * Uses getimagesize() for real MIME detection (not the browser-provided ext).
 */
function hero_image_validate(string $tmpName, string $origName, int $size): string
{
    if ($size > HERO_IMAGE_MAX_BYTES) {
        return basename($origName) . ' is over ' . (HERO_IMAGE_MAX_BYTES / 1024 / 1024) . ' MB.';
    }
    $imageInfo = @getimagesize((string) $tmpName);
    if (!is_array($imageInfo)) {
        return basename($origName) . ' is not a valid image file.';
    }
    $mime = $imageInfo['mime'] ?? '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return basename($origName) . ' is not a JPG, PNG or WebP image.';
    }
    return '';
}

/**
 * Safe extension derived from the real image MIME (never the browser filename).
 * Returns '' when the file is not a supported image.
 */
function hero_image_ext(string $tmpName): string
{
    $info = @getimagesize((string) $tmpName);
    if (!is_array($info)) {
        return '';
    }
    return ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? '';
}

/**
 * Optimize a hero image in place: downscale to max width and re-encode at a
 * sensible quality, preserving the existing extension. Best-effort — if GD is
 * unavailable or any step fails, the original file is left untouched.
 */
function hero_image_optimize(string $path): void
{
    if (!function_exists('imagecreatefromstring')) {
        return;
    }
    $info = @getimagesize($path);
    if (!is_array($info)) {
        return;
    }
    // Guard against decompression bombs before decoding into memory (~40 MP cap).
    if (((int) $info[0] * (int) $info[1]) > 40000000) {
        return;
    }
    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
    $data = @file_get_contents($path);
    if ($data === false) {
        return;
    }
    $src = @imagecreatefromstring($data);
    if ($src === false) {
        return;
    }
    $ow = imagesx($src);
    $oh = imagesy($src);
    $w = $ow;
    $h = $oh;
    if ($w > HERO_IMAGE_MAX_WIDTH) {
        $h = (int) round($h * HERO_IMAGE_MAX_WIDTH / $w);
        $w = HERO_IMAGE_MAX_WIDTH;
    }
    $canvas = imagecreatetruecolor($w, $h);
    if ($ext === 'png' || $ext === 'webp') {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
    }
    imagecopyresampled($canvas, $src, 0, 0, 0, 0, $w, $h, $ow, $oh);
    imagedestroy($src);
    if ($ext === 'webp' && function_exists('imagewebp')) {
        @imagewebp($canvas, $path, HERO_IMAGE_QUALITY);
    } elseif ($ext === 'png' && function_exists('imagepng')) {
        @imagepng($canvas, $path, 7);
    } elseif (function_exists('imagejpeg')) {
        @imagejpeg($canvas, $path, HERO_IMAGE_QUALITY);
    }
    imagedestroy($canvas);
}

/**
 * Normalize a datetime-local value ("2026-09-17T10:00") for storage so it
 * compares correctly against DATETIME columns on both MySQL and SQLite.
 */
function hero_datetime($value): ?string
{
    $v = trim((string) $value);
    if ($v === '') {
        return null;
    }
    $v = str_replace('T', ' ', $v);
    return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $v) ? $v : null;
}

/**
 * Stream a hero image to the browser. Public — the online shop needs it without auth.
 */
function stream_hero_image(string $filename): void
{
    if (!preg_match('/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/', $filename)) {
        http_response_code(404);
        exit;
    }
    $dir  = BASE_PATH . '/storage/uploads/hero';
    $real = realpath($dir);
    $path = realpath($dir . '/' . $filename);
    if ($real === false || $path === false || strpos($path, $real . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    $mime = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
    ][strtolower((string) pathinfo($filename, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

/* ---------------------------------------------------------------------------
 * Online Shop Feature Flags (Catalogue + WhatsApp mode)
 * ------------------------------------------------------------------------ */

function is_online_checkout_enabled(): bool
{
    return Setting::get('online_checkout_enabled', '0') === '1';
}

function is_mpesa_online_enabled(): bool
{
    // Separate flag for public online M-Pesa; POS M-Pesa uses mpesa_enabled separately.
    return Setting::get('mpesa_online_enabled', '0') === '1' && Setting::get('mpesa_enabled', '0') === '1';
}

function is_whatsapp_ordering_enabled(): bool
{
    // Default true for catalogue mode.
    $val = Setting::get('whatsapp_ordering_enabled', '1');
    return $val === '1' || $val === '';
}

/* ---------------------------------------------------------------------------
 * WhatsApp helpers
 * ------------------------------------------------------------------------ */

/**
 * Normalize a Kenyan WhatsApp number into international format without plus,
 * suitable for wa.me links.
 * Handles: 07XXXXXXXX, 7XXXXXXXX, 2547XXXXXXXX, +2547XXXXXXXX
 * Returns digits like 254712345678 or '' if invalid.
 */
function normalize_whatsapp_number(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    // Keep only digits and leading plus
    $hasPlus = str_starts_with($raw, '+');
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digits === '') {
        return '';
    }
    // Kenyan normalization: 07... (10 digits) -> 2547...
    if (strlen($digits) === 10 && str_starts_with($digits, '07')) {
        $digits = '254' . substr($digits, 1);
    } elseif (strlen($digits) === 10 && str_starts_with($digits, '01')) {
        $digits = '254' . substr($digits, 1);
    } elseif (strlen($digits) === 9 && ($digits[0] === '7' || $digits[0] === '1')) {
        $digits = '254' . $digits;
    } elseif (strlen($digits) === 12 && str_starts_with($digits, '254')) {
        // already good
    } elseif (strlen($digits) === 13 && str_starts_with($digits, '254') && $hasPlus) {
        // 254... with plus already stripped, keep
    } else {
        // For non-Kenyan numbers, if it starts with 0, we can't safely guess country.
        // Keep as-is if it looks like international (10-15 digits) and doesn't start with 0.
        if (str_starts_with($digits, '0')) {
            return '';
        }
        // Allow 10-15 digit international numbers
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return '';
        }
    }
    // Validate length 10-15
    if (strlen($digits) < 10 || strlen($digits) > 15) {
        return '';
    }
    return $digits;
}

function whatsapp_display_number(string $raw): string
{
    $norm = normalize_whatsapp_number($raw);
    if ($norm === '') {
        return trim($raw);
    }
    return '+' . $norm;
}

function whatsapp_number(): string
{
    $raw = Setting::get('whatsapp_sales_number', Setting::get('shop_phone', ''));
    $norm = normalize_whatsapp_number($raw);
    if ($norm !== '') {
        return $norm;
    }
    // Fallback: try shop_phone
    $fallback = Setting::get('shop_phone', '');
    $norm2 = normalize_whatsapp_number($fallback);
    return $norm2;
}

function whatsapp_link(string $message, ?string $number = null): string
{
    $num = $number ?? whatsapp_number();
    $num = normalize_whatsapp_number($num);
    $encoded = rawurlencode($message);
    if ($num !== '') {
        return 'https://wa.me/' . $num . '?text=' . $encoded;
    }
    return 'https://wa.me/?text=' . $encoded;
}

/**
 * Build a structured WhatsApp enquiry message for a product/variant.
 * Safe placeholders, no sensitive data.
 */
function build_whatsapp_message(array $product, ?array $variant, string $productUrl, ?string $customTemplate = null): string
{
    $shopName = Setting::get('shop_name', config('app.name', 'Khamis Computers'));
    $price = null;
    if ($variant && isset($variant['price_override']) && $variant['price_override'] !== null && (float)$variant['price_override'] > 0) {
        $price = gross_of((float)$variant['price_override']);
    } else {
        $price = gross_of((float)($product['sell_price'] ?? 0));
    }
    $priceStr = money($price);

    $productName = $product['name'] ?? 'Product';
    $sku = $variant['sku'] ?? $product['sku'] ?? '';
    $condition = $variant['condition_type'] ?? $product['condition_type'] ?? 'New';
    $condition = ucfirst((string)$condition);
    $ram = $variant['ram'] ?? '';
    $storage = $variant['storage'] ?? '';
    $colour = $variant['colour'] ?? '';
    $grade = $variant['grade'] ?? $product['condition_grade'] ?? '';
    $variantLabel = $variant['label'] ?? '';

    // Build variant string
    $variantParts = [];
    if ($ram !== '') $variantParts[] = $ram . ' RAM';
    if ($storage !== '') $variantParts[] = $storage;
    if ($colour !== '') $variantParts[] = $colour;
    if ($variantLabel !== '' && empty($variantParts)) $variantParts[] = $variantLabel;
    $variantStr = implode(' / ', $variantParts);
    if ($variantStr === '' && $variantLabel !== '') $variantStr = $variantLabel;

    $template = $customTemplate ?: Setting::get('whatsapp_message_template', '');
    if (trim($template) !== '') {
        // Safe placeholder replacement
        $replacements = [
            '{product_name}' => $productName,
            '{variant}' => $variantStr ?: ($variantLabel ?: 'Standard'),
            '{ram}' => $ram,
            '{storage}' => $storage,
            '{colour}' => $colour,
            '{color}' => $colour,
            '{condition}' => $condition,
            '{grade}' => $grade,
            '{price}' => $priceStr,
            '{sku}' => $sku,
            '{product_url}' => $productUrl,
            '{shop_name}' => $shopName,
        ];
        $msg = $template;
        foreach ($replacements as $k => $v) {
            $msg = str_replace($k, (string)$v, $msg);
        }
        // Ensure URL present
        if (!str_contains($msg, $productUrl)) {
            $msg .= "\n\nProduct page:\n" . $productUrl;
        }
        return trim($msg);
    }

    // Default structured template
    $lines = [];
    $lines[] = 'Hello ' . $shopName . ',';
    $lines[] = '';
    $lines[] = "I'm interested in this product:";
    $lines[] = '';
    $lines[] = 'Product: ' . $productName;
    if ($variantStr !== '') {
        $lines[] = 'Variant: ' . $variantStr;
    } elseif ($variantLabel !== '') {
        $lines[] = 'Variant: ' . $variantLabel;
    }
    if ($colour !== '') {
        $lines[] = 'Colour: ' . $colour;
    }
    if ($condition !== '') {
        $lines[] = 'Condition: ' . $condition;
    }
    if ($grade !== '') {
        $lines[] = 'Grade: ' . $grade;
    }
    $lines[] = 'Price shown: ' . $priceStr;
    if ($sku !== '') {
        $lines[] = 'Product Code: ' . $sku;
    }
    $lines[] = '';
    $lines[] = 'Product page:';
    $lines[] = $productUrl;
    $lines[] = '';
    $lines[] = 'Is this product currently available?';

    return implode("\n", $lines);
}

/* ---------------------------------------------------------------------------
 * Sale source helpers
 * ------------------------------------------------------------------------ */

function sale_source_options(): array
{
    return [
        'walk-in' => 'Walk-in',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Phone',
        'other' => 'Other',
        'online' => 'Website Checkout',
    ];
}

function sale_source_label(string $source): string
{
    $opts = sale_source_options();
    return $opts[$source] ?? ucfirst($source);
}

function condition_options(): array
{
    return [
        'new' => 'New',
        'used' => 'Used',
        'refurbished' => 'Refurbished',
    ];
}
