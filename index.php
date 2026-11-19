<?php
require_once __DIR__ . '/config/database.php';

$search = trim($_GET['q'] ?? '');
$category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;

$categories = [];
$category_result = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

$sql = "SELECT p.id, p.product_name, p.description, p.price, p.stock, p.image,
               c.category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}
if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}
$sql .= " ORDER BY p.created_at DESC, p.product_name ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

$page_title = 'Beranda';
require_once __DIR__ . '/includes/header.php';

function rupiah($amount) {
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}
?>
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <span class="eyebrow">PASAR SEGAR, BELANJA LEBIH MUDAH</span>
            <h1>Kebutuhan harian,<br><span>lebih dekat denganmu.</span></h1>
            <p>Temukan sayuran, buah, sembako, dan bahan pangan pilihan untuk kebutuhan rumah setiap hari.</p>
            <a href="#produk" class="btn btn-earth-primary">Jelajahi Produk <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-stamp">
            <span class="stamp-leaf">✳</span>
            <span>Segar<br>Setiap Hari</span>
        </div>
    </div>
</section>

<section class="container category-strip">
    <div class="section-heading">
        <div>
            <span class="eyebrow">PILIH SESUAI KEBUTUHAN</span>
            <h2>Kategori pilihan</h2>
        </div>
        <a class="text-link" href="index.php#produk">Lihat semua produk</a>
    </div>
    <div class="category-list">
        <a class="category-chip <?= $category_id === 0 ? 'active' : '' ?>" href="index.php#produk">Semua</a>
        <?php foreach ($categories as $category): ?>
            <a class="category-chip <?= $category_id === (int)$category['id'] ? 'active' : '' ?>"
               href="index.php?category=<?= (int)$category['id'] ?>#produk">
                <?= htmlspecialchars($category['category_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="container product-section" id="produk">
    <div class="section-heading align-items-end">
        <div>
            <span class="eyebrow">PILIHAN HARI INI</span>
            <h2>Produk untuk rumahmu</h2>
            <p class="section-subtitle">Belanja kebutuhan segar dalam beberapa langkah.</p>
        </div>
        <form class="search-form" method="get" action="index.php">
            <?php if ($category_id > 0): ?>
                <input type="hidden" name="category" value="<?= $category_id ?>">
            <?php endif; ?>
            <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari produk...">
            <button class="btn btn-earth-primary" type="submit">Cari</button>
        </form>
    </div>

    <?php if ($products->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <article class="product-card h-100">
                        <a class="product-visual" href="detail.php?id=<?= (int)$product['id'] ?>" aria-label="Lihat <?= htmlspecialchars($product['product_name']) ?>">
                            <?php
                            $image_file = $product['image'] ?? '';
                            $image_path = __DIR__ . '/uploads/' . $image_file;
                            ?>
                            <?php if ($image_file && is_file($image_path)): ?>
                                <img src="uploads/<?= rawurlencode($image_file) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <?php else: ?>
                                <div class="product-placeholder"><span>✳</span><small><?= htmlspecialchars($product['category_name']) ?></small></div>
                            <?php endif; ?>
                            <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                        </a>
                        <div class="product-info">
                            <h3><a href="detail.php?id=<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['product_name']) ?></a></h3>
                            <p class="product-price"><?= rupiah($product['price']) ?></p>
                            <div class="product-bottom">
                                <small>Stok: <?= (int)$product['stock'] ?></small>
                                <a href="detail.php?id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-earth-outline">Detail</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">⌕</div>
            <h3>Produk tidak ditemukan</h3>
            <p>Coba kata kunci atau kategori lainnya.</p>
            <a href="index.php" class="btn btn-earth-outline">Tampilkan semua produk</a>
        </div>
    <?php endif; ?>
</section>
<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
