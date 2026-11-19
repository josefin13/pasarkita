<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
$total_products = (int)$conn->query("SELECT COUNT(*) AS n FROM products")->fetch_assoc()['n'];
$total_categories = (int)$conn->query("SELECT COUNT(*) AS n FROM categories")->fetch_assoc()['n'];
$total_orders = (int)$conn->query("SELECT COUNT(*) AS n FROM orders")->fetch_assoc()['n'];
$pending_orders = (int)$conn->query("SELECT COUNT(*) AS n FROM orders WHERE status = 'Menunggu Konfirmasi'")->fetch_assoc()['n'];
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="admin-title h3 mb-1">Dashboard</h1><p class="text-muted">Ringkasan pengelolaan PasarKita.</p>
<div class="row g-3 mt-2">
<?php foreach ([['Total Produk',$total_products],['Kategori',$total_categories],['Total Pesanan',$total_orders],['Menunggu Konfirmasi',$pending_orders]] as $stat): ?>
<div class="col-sm-6 col-xl-3"><div class="card admin-card shadow-sm"><div class="card-body"><div class="text-muted small"><?= htmlspecialchars($stat[0]) ?></div><div class="display-6 fw-bold" style="color:#344e41"><?= $stat[1] ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="card admin-card mt-4"><div class="card-body">
<h2 class="h5 admin-title">Selamat datang di panel admin</h2>
<p class="text-muted mb-3">Gunakan menu di sebelah kiri untuk mengelola produk, kategori, dan pesanan.</p>
<a href="products.php" class="btn btn-admin">Kelola Produk</a>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
