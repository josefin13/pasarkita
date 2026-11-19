<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

$result = $conn->query(
    "SELECT id, order_code, customer_name, phone, total_amount,
            payment_method, status, created_at
     FROM orders
     ORDER BY created_at DESC"
);
$page_title = 'Pesanan';
require_once __DIR__ . '/includes/header.php';

function order_status_badge($status) {
    $map = [
        'Menunggu Konfirmasi' => 'warning',
        'Diproses' => 'info',
        'Selesai' => 'success',
        'Dibatalkan' => 'danger'
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $color . '">' . htmlspecialchars($status) . '</span>';
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="admin-title h3 mb-1">Pesanan</h1>
    <p class="text-muted mb-0">Periksa detail pesanan dan kelola status transaksi pembeli.</p>
  </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="card admin-card shadow-sm">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead><tr>
  <th>Kode Pesanan</th><th>Pembeli</th><th>Telepon</th><th>Total</th>
  <th>Pembayaran</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
</tr></thead>
<tbody>
<?php if ($result->num_rows): while ($o = $result->fetch_assoc()): ?>
<tr>
  <td class="fw-semibold"><?= htmlspecialchars($o['order_code']) ?></td>
  <td><?= htmlspecialchars($o['customer_name']) ?></td>
  <td><?= htmlspecialchars($o['phone']) ?></td>
  <td class="text-nowrap">Rp<?= number_format((float)$o['total_amount'], 0, ',', '.') ?></td>
  <td><?= htmlspecialchars($o['payment_method']) ?></td>
  <td><?= order_status_badge($o['status']) ?></td>
  <td class="text-nowrap"><?= htmlspecialchars($o['created_at']) ?></td>
  <td><a class="btn btn-sm btn-outline-success" href="order_detail.php?id=<?= (int)$o['id'] ?>">Detail</a></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="8" class="text-center text-muted py-4">Belum ada pesanan masuk.</td></tr>
<?php endif; ?>
</tbody></table>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
