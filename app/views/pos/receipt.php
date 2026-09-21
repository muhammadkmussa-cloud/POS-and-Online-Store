<?php
$shopName = Setting::get('shop_name', config('app.name'));
$footer   = Setting::get('receipt_footer', 'Thank you for shopping with us.');
$phone    = Setting::get('shop_phone', '');
$address  = Setting::get('shop_address', '');
?>
<div class="receipt-wrap">
    <div class="receipt-success" role="status">
        <span class="badge badge-green">Sale complete</span>
        <h1><?= e($sale['sale_number']) ?></h1>
        <p>The sale is recorded and stock has been updated. Print or download the receipt before starting the next sale.</p>
    </div>
    <div class="receipt card">
        <div class="receipt-head">
            <div class="receipt-brand">
                <?= receipt_logo(44) ?>
                <?php if ($address || $phone): ?>
                <div>
                    <?php if ($address): ?><span><?= e($address) ?></span><?php endif; ?>
                    <?php if ($phone): ?><span><?= e($phone) ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="receipt-title">TAX RECEIPT</div>
        </div>

        <div class="receipt-meta">
            <div><span>Receipt</span><b><?= e($sale['sale_number']) ?></b></div>
            <div><span>Date</span><b><?= e(date('d M Y · h:i A', strtotime($sale['created_at']))) ?></b></div>
            <div><span>Channel</span><b><?= e(strtoupper($sale['channel'])) ?></b></div>
            <div><span>Payment</span><b><?= e(strtoupper($sale['payment_method'])) ?><?= $sale['payment_ref'] ? ' · ' . e($sale['payment_ref']) : '' ?></b></div>
            <?php if ($sale['customer_name']): ?>
                <div><span>Customer</span><b><?= e($sale['customer_name']) ?><?= $sale['customer_phone'] ? ' · ' . e($sale['customer_phone']) : '' ?></b></div>
            <?php endif; ?>
            <div><span>Served by</span><b><?= e($sale['user_name'] ?? '—') ?></b></div>
        </div>

        <table class="receipt-items">
            <thead>
                <tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i): ?>
                <tr>
                    <td data-label="Item">
                        <div class="cell-main"><?= e($i['product_name']) ?></div>
                        <?php if ($i['serial_number']): ?><div class="cell-sub">SN: <?= e($i['serial_number']) ?></div><?php endif; ?>
                        <?php if (!empty($i['warranty_expires'])): ?><div class="cell-sub">Warranty until <?= e($i['warranty_expires']) ?></div><?php endif; ?>
                    </td>
                    <td class="num" data-label="Qty"><?= (int) $i['quantity'] ?></td>
                    <td class="num" data-label="Price"><?= money($i['unit_price']) ?></td>
                    <td class="num" data-label="Total"><?= money($i['line_total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-totals">
            <div><span>Subtotal</span><b><?= money($sale['subtotal']) ?></b></div>
            <?php if ((float) $sale['discount'] > 0): ?>
                <div class="discount"><span>Discount</span><b>− <?= money($sale['discount']) ?></b></div>
            <?php endif; ?>
            <div><span>VAT (<?= e((string) vat_rate()) ?>%)</span><b><?= money($sale['tax_amount']) ?></b></div>
            <div class="grand"><span>Total</span><b><?= money($sale['total']) ?></b></div>
        </div>

        <div class="receipt-footer">
            <p><?= e($footer) ?></p>
            <p class="muted">All prices include VAT unless shown otherwise. Warranty claims require this receipt.</p>
        </div>
    </div>

    <div class="receipt-actions">
        <a class="btn btn-primary" href="<?= e(url('pos')) ?>">New sale</a>
        <button class="btn btn-outline" type="button"
                onclick="window.open('<?= e(url('sales/' . $sale['id'] . '/print')) ?>', 'receipt', 'width=420,height=720')">Print receipt</button>
        <a class="btn btn-ghost" href="<?= e(url('sales/' . $sale['id'] . '/pdf')) ?>">Download PDF</a>
    </div>
</div>
