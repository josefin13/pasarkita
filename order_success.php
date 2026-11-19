<?php
require_once __DIR__ . '/config/database.php';
$code = trim($_GET['code'] ?? '');
if ($code === '') { http_response_code(404); exit('Pesanan tidak ditemukan.'); }
$stmt = $conn->prepare("SELECT order_code, customer_name, total_amount, payment_method, status, created_at FROM orders WHERE order_code = ? LIMIT 1");
$stmt->bind_param('s', $code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { http_response_code(404); exit('Pesanan tidak ditemukan.'); }
$itemsStmt = $conn->prepare("SELECT product_name, price, quantity, subtotal FROM order_items WHERE order_id = (SELECT id FROM orders WHERE order_code = ?)");
$itemsStmt->bind_param('s', $code);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
$page_title = 'Pesanan Berhasil';
require_once __DIR__ . '/includes/header.php';
function success_rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
?>
<section class="container py-5" style="max-width:800px">
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5 text-center">
        <div class="display-4 mb-2" style="color:#588157">✓</div>
        <span class="eyebrow">PESANAN TERCATAT</span>
        <h1 class="detail-title fs-2">Terima kasih, <?= htmlspecialchars($order['customer_name']) ?>!</h1>
        <p class="text-muted">Pesananmu berhasil disimpan. Simpan kode pesanan berikut untuk referensi.</p>
        <div class="rounded-3 p-3 my-4" style="background:#f6f3ea">
            <small class="text-muted d-block">Kode Pesanan</small>
            <strong class="fs-4" style="color:#344e41"><?= htmlspecialchars($order['order_code']) ?></strong>
        </div>
        <div class="text-start">
            <h2 class="h5 fw-bold" style="color:#344e41">Rincian Pesanan</h2>
            <?php while($item=$items->fetch_assoc()): ?>
                <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                    <span><?= htmlspecialchars($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span>
                    <strong class="text-nowrap"><?= success_rupiah($item['subtotal']) ?></strong>
                </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between mt-3"><strong>Total</strong><strong class="fs-5" style="color:#a66a4c"><?= success_rupiah($order['total_amount']) ?></strong></div>
            <div class="d-flex justify-content-between mt-2"><span class="text-muted">Pembayaran</span><strong><?= htmlspecialchars($order['payment_method']) ?></strong></div>
            <div class="d-flex justify-content-between mt-2"><span class="text-muted">Status</span><strong><?= htmlspecialchars($order['status']) ?></strong></div>
        </div>
        <a href="index.php" class="btn btn-earth-primary mt-4">Kembali ke Beranda</a>
    </div></div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
