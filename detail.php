<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

$stmt = $conn->prepare(
    "SELECT p.*, c.category_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

$page_title = $product['product_name'];
require_once __DIR__ . '/includes/header.php';

function rupiah_detail($amount) {
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}
$image_file = $product['image'] ?? '';
$image_path = __DIR__ . '/uploads/' . $image_file;
?>
<section class="container detail-section py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['product_name']) ?></li>
        </ol>
    </nav>
    <div class="row g-5 align-items-center">
        <div class="col-md-6">
            <div class="detail-visual">
                <?php if ($image_file && is_file($image_path)): ?>
                    <img src="uploads/<?= rawurlencode($image_file) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                <?php else: ?>
                    <div class="product-placeholder detail-placeholder"><span>✳</span><small><?= htmlspecialchars($product['category_name']) ?></small></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <span class="eyebrow"><?= htmlspecialchars($product['category_name']) ?></span>
            <h1 class="detail-title"><?= htmlspecialchars($product['product_name']) ?></h1>
            <div class="detail-price"><?= rupiah_detail($product['price']) ?></div>
            <p class="detail-description"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            <p class="stock-label">Ketersediaan: <strong><?= (int)$product['stock'] > 0 ? 'Stok tersedia (' . (int)$product['stock'] . ')' : 'Stok habis' ?></strong></p>
            <form method="post" action="cart.php" class="add-cart-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <label for="quantity" class="form-label">Jumlah</label>
                <div class="d-flex gap-2">
                    <input id="quantity" class="form-control quantity-input" type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>
                    <button class="btn btn-earth-primary flex-grow-1" type="submit" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>Tambah ke Keranjang</button>
                </div>
            </form>
            <p class="small text-muted mt-3 mb-0">Pembayaran dilakukan secara COD saat pesanan diterima.</p>
        </div>
    </div>
</section>
<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
