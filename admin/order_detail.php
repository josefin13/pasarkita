<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: orders.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$itemStmt = $conn->prepare(
    "SELECT product_name, price, quantity, subtotal
     FROM order_items WHERE order_id = ? ORDER BY id ASC"
);
$itemStmt->bind_param('i', $id);
$itemStmt->execute();
$items = $itemStmt->get_result();

$statusMap = [
    'Menunggu Konfirmasi' => 'warning',
    'Diproses' => 'info',
    'Selesai' => 'success',
    'Dibatalkan' => 'danger'
];
$statusColor = $statusMap[$order['status']] ?? 'secondary';
$canProcess = $order['status'] === 'Menunggu Konfirmasi';
$canFinish = $order['status'] === 'Diproses';
$canCancel = in_array($order['status'], ['Menunggu Konfirmasi', 'Diproses'], true);

$page_title = 'Detail Pesanan';
require_once __DIR__ . '/includes/header.php';
function detail_order_rupiah($n) {
    return 'Rp' . number_format((float)$n, 0, ',', '.');
}
?>
<div class="mb-3">
  <a href="orders.php" class="text-decoration-none" style="color:#76523b">← Kembali ke daftar pesanan</a>
</div>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <h1 class="admin-title h3 mb-1">Detail Pesanan</h1>
    <p class="text-muted mb-0"><?= htmlspecialchars($order['order_code']) ?></p>
  </div>
  <span class="badge text-bg-<?= $statusColor ?> fs-6"><?= htmlspecialchars($order['status']) ?></span>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card admin-card shadow-sm mb-3">
      <div class="card-body p-4">
        <h2 class="h5 admin-title mb-3">Informasi Pembeli</h2>
        <div class="row g-3">
          <div class="col-sm-6"><div class="text-muted small">Nama Pembeli</div><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
          <div class="col-sm-6"><div class="text-muted small">Nomor Telepon</div><strong><?= htmlspecialchars($order['phone']) ?></strong></div>
          <div class="col-12"><div class="text-muted small">Alamat Pengiriman</div><div class="p-3 rounded-3" style="background:#f6f3ea;white-space:pre-wrap"><?= htmlspecialchars($order['address']) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card admin-card shadow-sm">
      <div class="card-body p-4">
        <h2 class="h5 admin-title mb-3">Produk yang Dipesan</h2>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php if ($items->num_rows): while ($item = $items->fetch_assoc()): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-nowrap"><?= detail_order_rupiah($item['price']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td class="text-nowrap"><?= detail_order_rupiah($item['subtotal']) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" class="text-muted text-center">Tidak ada rincian produk.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between border-top pt-3">
          <strong>Total Pesanan</strong>
          <strong class="fs-5" style="color:#a66a4c"><?= detail_order_rupiah($order['total_amount']) ?></strong>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card admin-card shadow-sm mb-3">
      <div class="card-body p-4">
        <h2 class="h5 admin-title mb-3">Informasi Transaksi</h2>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Kode</span><strong><?= htmlspecialchars($order['order_code']) ?></strong></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Pembayaran</span><strong><?= htmlspecialchars($order['payment_method']) ?></strong></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Dibuat</span><strong><?= htmlspecialchars($order['created_at']) ?></strong></div>
        <div class="d-flex justify-content-between py-2"><span class="text-muted">Status</span><strong><?= htmlspecialchars($order['status']) ?></strong></div>
      </div>
    </div>

    <div class="card admin-card shadow-sm">
      <div class="card-body p-4">
        <h2 class="h5 admin-title mb-2">Tindakan Pesanan</h2>
        <?php if ($canProcess): ?>
          <p class="small text-muted">Periksa nama, alamat, nomor telepon, dan ketersediaan produk. Jika sesuai, konfirmasikan pesanan agar mulai diproses.</p>
          <form method="post" action="order_status.php" onsubmit="return confirm('Konfirmasi pesanan ini dan ubah status menjadi Diproses?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <input type="hidden" name="new_status" value="Diproses">
            <button class="btn btn-success w-100">✓ Konfirmasi Pesanan</button>
          </form>
        <?php elseif ($canFinish): ?>
          <p class="small text-muted">Pesanan sedang disiapkan. Setelah pesanan diterima pembeli dan pembayaran COD diterima, tandai pesanan sebagai selesai.</p>
          <form method="post" action="order_status.php" onsubmit="return confirm('Tandai pesanan ini sebagai Selesai?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <input type="hidden" name="new_status" value="Selesai">
            <button class="btn btn-success w-100">Tandai Selesai</button>
          </form>
        <?php elseif ($order['status'] === 'Selesai'): ?>
          <div class="alert alert-success mb-0">Pesanan telah selesai. Pembayaran COD dianggap telah diterima.</div>
        <?php else: ?>
          <div class="alert alert-danger mb-0">Pesanan ini telah dibatalkan.</div>
        <?php endif; ?>

        <?php if ($canCancel): ?>
          <hr>
          <p class="small text-muted mb-2">Jika pesanan tidak dapat dipenuhi, admin dapat membatalkannya. Stok produk akan dikembalikan otomatis.</p>
          <form method="post" action="order_status.php" onsubmit="return confirm('Batalkan pesanan ini? Stok produk akan dikembalikan.')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <input type="hidden" name="new_status" value="Dibatalkan">
            <button class="btn btn-outline-danger w-100">Batalkan Pesanan</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
