<?php
$serialized = (int) $product['is_serialized'] === 1;
$warrantyMonths = (int) ($product['warranty_months'] ?? 0);
$mainImage = $images[0] ?? null;
foreach ($images as $candidate) {
    if ($candidate['filename'] === ($product['image'] ?? null)) { $mainImage = $candidate; break; }
}
$checkoutEnabled = $checkoutEnabled ?? is_online_checkout_enabled();
$whatsappEnabled = $whatsappEnabled ?? is_whatsapp_ordering_enabled();
$whatsappNumber = $whatsappNumber ?? whatsapp_number();
$shopName = $shopName ?? Setting::get('shop_name', config('app.name'));
$variants = $variants ?? [];
$conditionType = $product['condition_type'] ?? 'new';
$conditionGrade = $product['condition_grade'] ?? '';
$conditionNotes = $product['condition_notes'] ?? '';
$batteryNotes = $product['battery_notes'] ?? '';
$hasVariants = !empty($variants);

// Prepare distinct attribute values for selector UI
$rams = [];
$storages = [];
$colours = [];
$conditions = [];
foreach ($variants as $v) {
    if (!empty($v['ram']) && !in_array($v['ram'], $rams)) $rams[] = $v['ram'];
    if (!empty($v['storage']) && !in_array($v['storage'], $storages)) $storages[] = $v['storage'];
    if (!empty($v['colour']) && !in_array($v['colour'], $colours)) $colours[] = $v['colour'];
    if (!empty($v['condition_type']) && !in_array($v['condition_type'], $conditions)) $conditions[] = $v['condition_type'];
}

// Default variant is first active
$defaultVariant = $hasVariants ? $variants[0] : null;
$basePrice = (float)$product['sell_price'];
$effectivePrice = $defaultVariant ? ProductVariant::effectivePrice($defaultVariant, $product) : $basePrice;
$fromPrice = $hasVariants ? Product::fromPrice($product) : $basePrice;

$productUrl = url('shop/product/' . $product['id']);
$initialSku = $defaultVariant['sku'] ?? $product['sku'];
?>
<div class="product-breadcrumb"><a href="<?= e(url('shop/products')) ?>">Products</a><span>/</span><span><?= e($product['category_name'] ?? 'Product') ?></span></div>
<section class="product-detail">
    <div class="product-gallery">
        <div class="detail-thumb">
            <?php if ($images): ?>
                <img id="main-image" src="<?= e(url('uploads/p/' . rawurlencode($mainImage['filename']))) ?>" alt="<?= e($mainImage['alt_text'] ?: $product['name']) ?>">
            <?php else: ?>
                <?= product_thumb($product, 620) ?>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs" aria-label="Product images">
                <?php foreach ($images as $index => $im): ?>
                    <button type="button" class="gallery-thumb <?= $im['filename'] === $mainImage['filename'] ? 'active' : '' ?>" data-gallery-src="<?= e(url('uploads/p/' . rawurlencode($im['filename']))) ?>" data-gallery-alt="<?= e($im['alt_text'] ?: $product['name']) ?>" aria-label="Show image <?= $index + 1 ?>: <?= e($im['alt_text'] ?: $product['name']) ?>">
                        <img src="<?= e(url('uploads/p/' . rawurlencode($im['filename']))) ?>" alt="">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-info">
        <div class="section-kicker"><?= e($product['brand_name'] ? $product['brand_name'] . ' · ' : '') ?><?= e($product['category_name'] ?? '') ?></div>
        <h1 id="product-name-display"><?= e($product['name']) ?></h1>
        <div class="detail-rating"><span aria-hidden="true">★★★★★</span> <span>Local stock and support</span></div>

        <div class="detail-price-wrap">
            <div class="detail-price" id="detail-price" data-base-price="<?= e((string)$basePrice) ?>" data-from-price="<?= e((string)$fromPrice) ?>"><?= money(gross_of($effectivePrice)) ?></div>
            <?php if ($hasVariants): ?>
                <div class="detail-price-from" id="detail-price-from" style="<?= $effectivePrice != $fromPrice ? 'display:none' : '' ?>"><small>From <?= money(gross_of($fromPrice)) ?></small></div>
            <?php endif; ?>
            <div class="detail-price-ex">VAT included · <span id="detail-price-ex"><?= money($effectivePrice) ?></span> before VAT</div>
        </div>

        <div class="condition-badge-row">
            <span class="badge badge-<?= strtolower($conditionType) === 'new' ? 'green' : (strtolower($conditionType) === 'refurbished' ? 'blue' : 'orange') ?>" id="condition-badge"><?= e(ucfirst($conditionType ?: 'New')) ?></span>
            <?php if ($conditionGrade !== ''): ?><span class="badge badge-gray" id="grade-badge">Grade: <?= e($conditionGrade) ?></span><?php endif; ?>
            <?php if ($serialized): ?><span class="badge badge-blue">Serial / IMEI tracked</span><?php endif; ?>
        </div>

        <div class="availability-card">
            <div><span class="availability-dot <?= $stock > 0 ? 'available' : '' ?>" id="availability-dot"></span><b id="availability-text"><?= $stock > 0 ? ($stock <= 3 ? 'Only ' . $stock . ' available' : 'In stock now') : 'Currently out of stock' ?></b><small id="availability-sub"><?= $stock > 0 ? 'Enquire on WhatsApp for confirmation' : 'Contact us for restock timing' ?></small></div>
            <div><span aria-hidden="true">⌂</span><b>Store pickup</b><small><?= $stock > 0 ? 'Usually ready the same business day' : 'Unavailable' ?></small></div>
            <div><span aria-hidden="true">↗</span><b>Nairobi delivery</b><small>Fee confirmed on WhatsApp</small></div>
        </div>

        <?php if ($hasVariants): ?>
        <div class="variant-selectors" id="variant-selectors" aria-label="Product variants">
            <?php if (!empty($storages)): ?>
                <div class="variant-group" data-variant-group="storage">
                    <span class="variant-label">Storage:</span>
                    <div class="variant-options" role="group" aria-label="Storage">
                        <?php foreach ($storages as $opt): ?>
                            <button type="button" class="variant-option" data-value="<?= e($opt) ?>" data-attr="storage"><?= e($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($rams)): ?>
                <div class="variant-group" data-variant-group="ram">
                    <span class="variant-label">RAM:</span>
                    <div class="variant-options" role="group" aria-label="RAM">
                        <?php foreach ($rams as $opt): ?>
                            <button type="button" class="variant-option" data-value="<?= e($opt) ?>" data-attr="ram"><?= e($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($colours)): ?>
                <div class="variant-group" data-variant-group="colour">
                    <span class="variant-label">Colour:</span>
                    <div class="variant-options" role="group" aria-label="Colour">
                        <?php foreach ($colours as $opt): ?>
                            <button type="button" class="variant-option" data-value="<?= e($opt) ?>" data-attr="colour"><?= e($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($conditions)): ?>
                <div class="variant-group" data-variant-group="condition_type">
                    <span class="variant-label">Condition:</span>
                    <div class="variant-options" role="group" aria-label="Condition">
                        <?php foreach ($conditions as $opt): ?>
                            <button type="button" class="variant-option" data-value="<?= e($opt) ?>" data-attr="condition_type"><?= e(ucfirst($opt)) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (count($variants) > 1 && empty($rams) && empty($storages) && empty($colours)): ?>
                <div class="variant-group" data-variant-group="label">
                    <span class="variant-label">Options:</span>
                    <div class="variant-options" role="group" aria-label="Options">
                        <?php foreach ($variants as $v): ?>
                            <button type="button" class="variant-option" data-variant-id="<?= (int)$v['id'] ?>"><?= e(ProductVariant::displayLabel($v)) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="selected-variant-summary" id="selected-variant-summary" aria-live="polite"></div>
        </div>
        <?php endif; ?>

        <?php if ($product['description']): ?><div class="product-description"><?= nl2br(e($product['description'])) ?></div><?php endif; ?>

        <?php if ($conditionNotes !== ''): ?>
            <div class="condition-notes"><b>Condition notes:</b> <?= nl2br(e($conditionNotes)) ?></div>
        <?php endif; ?>
        <?php if ($batteryNotes !== ''): ?>
            <div class="condition-notes"><b>Battery:</b> <?= e($batteryNotes) ?></div>
        <?php endif; ?>

        <div class="purchase-panel" id="purchase-panel">
            <?php if ($checkoutEnabled): ?>
                <?php if ($stock > 0): ?>
                    <?php if (!$serialized): ?><label class="quantity-field" for="qty-input"><span>Quantity</span><input type="number" id="qty-input" value="1" min="1" max="<?= $stock ?>" inputmode="numeric"></label><?php endif; ?>
                    <button class="btn btn-primary btn-lg" id="add-to-cart" data-add="<?= (int) $product['id'] ?>" data-stock="<?= $stock ?>" data-serialized="<?= $serialized ? '1' : '0' ?>" data-name="<?= e($product['name']) ?>">Add to cart</button>
                <?php else: ?>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('shop/products')) ?>">Find an alternative</a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($whatsappEnabled): ?>
                    <?php if ($stock > 0): ?>
                        <a class="btn btn-whatsapp btn-lg" id="whatsapp-order-btn" href="#" target="_blank" rel="noopener" aria-label="Order on WhatsApp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.11 6.36 2.1 11.81c0 1.72.46 3.41 1.33 4.89L2.05 22l5.44-1.41c1.43.77 3.04 1.18 4.67 1.18h.01c5.46 0 9.93-4.36 9.93-9.81 0-2.62-1.03-5.08-2.9-6.93A9.89 9.89 0 0012.04 2zm0 1.8a8.09 8.09 0 015.74 2.34 8 8 0 012.38 5.66c0 4.44-3.68 8.01-8.12 8.01h-.01a8.14 8.14 0 01-3.99-1.02l-.29-.17-3.23.84.86-3.12-.19-.31a6.56 6.56 0 01-1.01-3.52c0-4.44 3.69-8.01 8.12-8.01zm-3.3 3.34c-.14-.31-.29-.31-.43-.32h-.36c-.13 0-.34.05-.52.24-.18.2-.7.67-.7 1.63 0 .96.71 1.89.81 2.02.1.13 1.39 2.15 3.41 2.96 1.68.67 2.02.54 2.38.51.36-.04 1.16-.47 1.33-.92.16-.46.16-.85.11-.93-.04-.08-.15-.13-.31-.21-.16-.08-.95-.47-1.1-.52-.14-.05-.25-.08-.36.08-.1.16-.42.52-.51.63-.1.11-.19.12-.35.04-.16-.08-.68-.25-1.29-.8-.48-.42-.8-.94-.89-1.1-.1-.16-.01-.25.07-.33.07-.07.16-.19.24-.28.08-.1.1-.16.16-.27.05-.11.03-.2-.01-.28-.04-.08-.36-.86-.49-1.18z"/></svg>
                            Order on WhatsApp
                        </a>
                        <button type="button" class="btn btn-outline btn-sm" id="share-product-btn" aria-label="Share product">Share</button>
                    <?php else: ?>
                        <a class="btn btn-whatsapp btn-lg btn-out-of-stock" id="whatsapp-order-btn" href="#" target="_blank" rel="noopener" aria-label="Ask about availability on WhatsApp">
                            Ask About Availability
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('shop/products')) ?>">Browse catalogue</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <dl class="product-specs">
            <div><dt>SKU</dt><dd id="sku-display"><?= e($initialSku) ?></dd></div>
            <div><dt>Warranty</dt><dd><?= $warrantyMonths > 0 ? $warrantyMonths . ' month' . ($warrantyMonths === 1 ? '' : 's') : 'Receipt-backed store support' ?></dd></div>
            <?php if ($conditionGrade !== ''): ?><div><dt>Grade</dt><dd id="grade-display"><?= e($conditionGrade) ?></dd></div><?php endif; ?>
            <div><dt>Tracking</dt><dd><?= $serialized ? 'Serial / IMEI recorded on receipt' : 'Quantity tracked' ?></dd></div>
            <div><dt>Availability</dt><dd id="stock-display"><?= $stock > 0 ? $stock . ' in stock' : 'Out of stock — enquire' ?></dd></div>
        </dl>
    </div>
</section>

<div class="mobile-buy-bar" id="mobile-buy-bar" style="<?= $stock <=0 && !$hasVariants ? '' : '' ?>">
    <div><small id="mobile-price-label"><?= $hasVariants ? 'From ' : '' ?>VAT included</small><b id="mobile-price"><?= money(gross_of($effectivePrice)) ?></b></div>
    <?php if ($checkoutEnabled && $stock > 0): ?>
        <button class="btn btn-primary" type="button" data-mobile-add>Add to cart</button>
    <?php elseif ($whatsappEnabled): ?>
        <a class="btn btn-whatsapp" id="mobile-whatsapp-btn" href="#" target="_blank" rel="noopener">💬 WhatsApp</a>
    <?php endif; ?>
</div>

<?php if ($related): ?>
<section class="shop-section related-section">
    <div class="shop-section-head"><div><div class="section-kicker">More to explore</div><h2>You might also like</h2></div></div>
    <div class="product-grid"><?php foreach ($related as $p): include APP_PATH . '/views/shop/_product-card.php'; endforeach; ?></div>
</section>
<?php endif; ?>

<script type="application/json" id="product-data"><?= json_encode([
    'id' => (int)$product['id'],
    'name' => $product['name'],
    'sku' => $product['sku'],
    'sell_price' => (float)$product['sell_price'],
    'stock' => $stock,
    'condition_type' => $conditionType,
    'condition_grade' => $conditionGrade,
    'url' => $productUrl,
    'shop_name' => $shopName,
    'whatsapp_number' => $whatsappNumber,
]) ?></script>
<script type="application/json" id="variant-data"><?= json_encode($variants) ?></script>

<script>
(function(){
    var productEl = document.getElementById('product-data');
    var variantEl = document.getElementById('variant-data');
    if (!productEl) return;
    var product = JSON.parse(productEl.textContent);
    var variants = variantEl ? JSON.parse(variantEl.textContent) : [];
    var hasVariants = variants.length > 0;
    var selected = hasVariants ? variants[0] : null;
    var selectors = document.getElementById('variant-selectors');
    var priceEl = document.getElementById('detail-price');
    var priceExEl = document.getElementById('detail-price-ex');
    var skuEl = document.getElementById('sku-display');
    var conditionBadge = document.getElementById('condition-badge');
    var gradeBadge = document.getElementById('grade-badge');
    var gradeDisplay = document.getElementById('grade-display');
    var summaryEl = document.getElementById('selected-variant-summary');
    var waBtn = document.getElementById('whatsapp-order-btn');
    var mobileWaBtn = document.getElementById('mobile-whatsapp-btn');
    var mobilePrice = document.getElementById('mobile-price');
    var stockDisplay = document.getElementById('stock-display');

    function money(val){
        var currency = (window.KC_SHOP_CONFIG && window.KC_SHOP_CONFIG.currency) || 'KSh';
        // Use same formatting as PHP money()
        return currency + ' ' + Number(val).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    function gross(net){
        var vat = <?= json_encode((float)vat_rate()) ?>;
        return net * (1 + vat/100);
    }
    function displayVariantLabel(v){
        if (!v) return 'Standard';
        if (v.label) return v.label;
        var parts=[];
        if (v.ram) parts.push(v.ram + ' RAM');
        if (v.storage) parts.push(v.storage);
        if (v.colour) parts.push(v.colour);
        if (v.condition_type) parts.push(v.condition_type.charAt(0).toUpperCase()+v.condition_type.slice(1));
        return parts.length ? parts.join(' / ') : 'Standard';
    }
    function findVariantByAttributes(attrs){
        // attrs: {ram, storage, colour, condition_type, variant_id}
        if (attrs.variant_id) {
            return variants.find(function(v){ return parseInt(v.id)===parseInt(attrs.variant_id); }) || null;
        }
        var candidates = variants.slice();
        ['ram','storage','colour','condition_type'].forEach(function(key){
            if (attrs[key]) {
                candidates = candidates.filter(function(v){ return (v[key]||'').toLowerCase() === attrs[key].toLowerCase(); });
            }
        });
        return candidates[0] || null;
    }
    function updateUI(){
        var price = selected ? (selected.price_override ? parseFloat(selected.price_override) : product.sell_price) : product.sell_price;
        var grossPrice = gross(price);
        if (priceEl) priceEl.textContent = money(grossPrice);
        if (priceExEl) priceExEl.textContent = money(price);
        if (mobilePrice) mobilePrice.textContent = money(grossPrice);
        if (skuEl) skuEl.textContent = (selected && selected.sku) ? selected.sku : product.sku;
        if (conditionBadge) {
            var cond = (selected && selected.condition_type) ? selected.condition_type : product.condition_type;
            conditionBadge.textContent = cond ? cond.charAt(0).toUpperCase()+cond.slice(1) : 'New';
        }
        if (gradeBadge && selected) {
            if (selected.grade) { gradeBadge.textContent = 'Grade: ' + selected.grade; gradeBadge.style.display=''; }
            else { gradeBadge.style.display='none'; }
        }
        if (gradeDisplay && selected) {
            if (selected.grade) { gradeDisplay.textContent = selected.grade; gradeDisplay.parentElement.style.display=''; }
        }
        if (summaryEl) {
            summaryEl.innerHTML = '<b>Selected:</b> ' + escapeHtml(displayVariantLabel(selected)) + ' — <b>' + escapeHtml(money(grossPrice)) + '</b>';
        }
        updateWhatsappLink();
    }
    function escapeHtml(s){
        return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
    }
    function buildMessage(){
        var shopName = product.shop_name || 'Khamis Computers';
        var productName = product.name;
        var variantLabel = displayVariantLabel(selected);
        var colour = selected ? (selected.colour||'') : '';
        var ram = selected ? (selected.ram||'') : '';
        var storage = selected ? (selected.storage||'') : '';
        var condition = (selected && selected.condition_type) ? selected.condition_type : (product.condition_type||'New');
        condition = condition.charAt(0).toUpperCase()+condition.slice(1);
        var price = selected ? (selected.price_override ? parseFloat(selected.price_override) : product.sell_price) : product.sell_price;
        var priceStr = money(gross(price));
        var sku = (selected && selected.sku) ? selected.sku : product.sku;
        var url = product.url;
        var grade = selected ? (selected.grade||'') : (product.condition_grade||'');

        var lines=[];
        lines.push('Hello ' + shopName + ',');
        lines.push('');
        lines.push("I'm interested in this product:");
        lines.push('');
        lines.push('Product: ' + productName);
        if (variantLabel && variantLabel!=='Standard') {
            lines.push('Variant: ' + variantLabel);
        }
        if (ram) lines.push('RAM: ' + ram);
        if (storage) lines.push('Storage: ' + storage);
        if (colour) lines.push('Colour: ' + colour);
        lines.push('Condition: ' + condition);
        if (grade) lines.push('Grade: ' + grade);
        lines.push('Price shown: ' + priceStr);
        if (sku) lines.push('Product Code: ' + sku);
        lines.push('');
        lines.push('Product page:');
        lines.push(url);
        lines.push('');
        if (product.stock <=0) {
            lines.push("I'm interested in this product. It is currently shown as out of stock. Please let me know if/when it becomes available.");
        } else {
            lines.push('Is this product currently available?');
        }
        return lines.join('\n');
    }
    function updateWhatsappLink(){
        if (!waBtn && !mobileWaBtn) return;
        var msg = buildMessage();
        var num = product.whatsapp_number || (window.KC_SHOP_CONFIG && window.KC_SHOP_CONFIG.whatsappNumber) || '';
        var base = 'https://wa.me/' + (num ? num.replace(/\D/g,'') : '') + '?text=';
        var link = base + encodeURIComponent(msg);
        if (waBtn) waBtn.href = link;
        if (mobileWaBtn) mobileWaBtn.href = link;
        // Store for tracking
        window._kc_last_whatsapp_message = msg;
        window._kc_last_whatsapp_variant = selected;
    }

    // Variant selector interaction
    if (selectors) {
        var state = {ram:null, storage:null, colour:null, condition_type:null, variant_id:null};
        // Initialize first options as active if single attribute
        selectors.querySelectorAll('.variant-group').forEach(function(group){
            var first = group.querySelector('.variant-option');
            if (first) {
                // Don't auto-select if multiple groups, let user choose, but select first variant initially
                // We'll mark the default variant's attributes as active
            }
        });
        // Mark default variant active
        if (selected) {
            if (selected.ram) state.ram = selected.ram;
            if (selected.storage) state.storage = selected.storage;
            if (selected.colour) state.colour = selected.colour;
            if (selected.condition_type) state.condition_type = selected.condition_type;
            state.variant_id = selected.id;
            // Apply active classes
            selectors.querySelectorAll('.variant-option').forEach(function(btn){
                var attr = btn.dataset.attr;
                var val = btn.dataset.value;
                var vid = btn.dataset.variantId;
                if (vid && parseInt(vid)===parseInt(selected.id)) btn.classList.add('active');
                else if (attr && val) {
                    if (state[attr] && state[attr].toLowerCase()===val.toLowerCase()) btn.classList.add('active');
                }
            });
        }

        selectors.addEventListener('click', function(e){
            var btn = e.target.closest('.variant-option');
            if (!btn) return;
            var attr = btn.dataset.attr;
            var val = btn.dataset.value;
            var vid = btn.dataset.variantId;
            if (vid) {
                state = {ram:null, storage:null, colour:null, condition_type:null, variant_id: parseInt(vid)};
                selected = variants.find(function(v){ return parseInt(v.id)===parseInt(vid); }) || null;
                // Reset active
                selectors.querySelectorAll('.variant-option').forEach(function(b){ b.classList.remove('active'); });
                btn.classList.add('active');
            } else if (attr && val) {
                // Toggle same value off? Keep selected
                state[attr] = val;
                state.variant_id = null;
                // Update active in group
                var group = btn.closest('.variant-group');
                if (group) {
                    group.querySelectorAll('.variant-option').forEach(function(b){ b.classList.remove('active'); });
                    btn.classList.add('active');
                }
                var found = findVariantByAttributes(state);
                if (found) selected = found;
                else {
                    // If no exact match, keep state but create synthetic selected from state
                    selected = {
                        id: null,
                        label: null,
                        ram: state.ram,
                        storage: state.storage,
                        colour: state.colour,
                        condition_type: state.condition_type,
                        grade: null,
                        sku: product.sku,
                        price_override: null
                    };
                }
            }
            updateUI();
        });
    }

    // Share button
    var shareBtn = document.getElementById('share-product-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function(){
            var shareData = {title: product.name, text: product.name + ' - available at ' + product.shop_name, url: product.url};
            if (navigator.share) {
                navigator.share(shareData).catch(function(){});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(product.url).then(function(){
                    if (window.KC && window.KC.toast) window.KC.toast('Link copied', 'success');
                });
            }
        });
    }

    // WhatsApp click tracking (lightweight lead)
    function trackWhatsapp(){
        try {
            var cfg = window.KC_SHOP_CONFIG || {};
            var payload = {
                product_id: product.id,
                variant_id: selected ? selected.id : null,
                product_name: product.name,
                variant_label: displayVariantLabel(selected),
                price_shown: selected ? (selected.price_override ? parseFloat(selected.price_override) : product.sell_price) : product.sell_price,
                product_url: product.url || window.location.href,
                source_page: window.location.href,
                condition_type: selected ? (selected.condition_type || product.condition_type || '') : (product.condition_type || '')
            };
            fetch(cfg.whatsappEnquiryUrl || '/shop/whatsapp-enquiry', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-Token': cfg.csrf || ''},
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(function(){});
        } catch(e){}
    }
    if (waBtn) waBtn.addEventListener('click', trackWhatsapp);
    if (mobileWaBtn) mobileWaBtn.addEventListener('click', trackWhatsapp);

    updateUI();
})();
</script>
