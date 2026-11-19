<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

$sid = session_id();
$cartStmt = $conn->prepare("SELECT id FROM carts WHERE session_id = ? LIMIT 1");
$cartStmt->bind_param('s', $sid);
$cartStmt->execute();
$cartRow = $cartStmt->get_result()->fetch_assoc();
$cart_id = $cartRow ? (int)$cartRow['id'] : 0;

if (!$cart_id) {
    $_SESSION['cart_error'] = 'Keranjang masih kosong.';
    header('Location: cart.php'); exit;
}

$load = $conn->prepare(
    "SELECT ci.quantity, p.id AS product_id, p.product_name, p.price, p.stock
     FROM cart_items ci JOIN products p ON p.id = ci.product_id
     WHERE ci.cart_id = ?"
);
$load->bind_param('i', $cart_id);
$load->execute();
$items = [];
$total = 0;
$res = $load->get_result();
while ($r = $res->fetch_assoc()) { $items[] = $r; $total += (float)$r['price'] * (int)$r['quantity']; }
if (!$items) { $_SESSION['cart_error']='Keranjang masih kosong.'; header('Location: cart.php'); exit; }

$error = '';
$name = ''; $phone = ''; $address = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $error = 'Nama harus terdiri dari 3 sampai 100 karakter.';
    } elseif (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        $error = 'Masukkan nomor telepon yang valid (8–20 karakter).';
    } elseif (mb_strlen($address) < 10 || mb_strlen($address) > 2000) {
        $error = 'Alamat harus terdiri dari minimal 10 karakter.';
    } else {
        try {
            $conn->begin_transaction();

            $lockCart = $conn->prepare(
                "SELECT ci.product_id, ci.quantity, p.product_name, p.price, p.stock
                 FROM cart_items ci
                 JOIN products p ON p.id = ci.product_id
                 WHERE ci.cart_id = ? FOR UPDATE"
            );
            $lockCart->bind_param('i', $cart_id);
            $lockCart->execute();
            $lockedItems = [];
            $grandTotal = 0;
            $lockedResult = $lockCart->get_result();
            while ($r = $lockedResult->fetch_assoc()) {
                if ((int)$r['quantity'] > (int)$r['stock']) {
                    throw new RuntimeException('Stok ' . $r['product_name'] . ' tidak mencukupi. Silakan perbarui keranjang.');
                }
                $r['subtotal'] = (float)$r['price'] * (int)$r['quantity'];
                $grandTotal += $r['subtotal'];
                $lockedItems[] = $r;
            }
            if (!$lockedItems) throw new RuntimeException('Keranjang kosong. Silakan pilih produk terlebih dahulu.');

            $orderCode = 'PK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $payment = 'COD';
            $status = 'Menunggu Konfirmasi';
            $insertOrder = $conn->prepare(
                "INSERT INTO orders (order_code, customer_name, phone, address, total_amount, payment_method, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $insertOrder->bind_param('ssssdss', $orderCode, $name, $phone, $address, $grandTotal, $payment, $status);
            $insertOrder->execute();
            $orderId = (int)$conn->insert_id;

            $insertItem = $conn->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($lockedItems as $item) {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['quantity'];
                $price = (float)$item['price'];
                $subtotal = (float)$item['subtotal'];
                $pname = $item['product_name'];
                $insertItem->bind_param('iisdid', $orderId, $pid, $pname, $price, $qty, $subtotal);
                $insertItem->execute();

                $updateStock->bind_param('iii', $qty, $pid, $qty);
                $updateStock->execute();
                if ($updateStock->affected_rows !== 1) {
                    throw new RuntimeException('Stok produk berubah. Silakan ulangi checkout.');
                }
            }

            $clear = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $clear->bind_param('i', $cart_id);
            $clear->execute();

            $conn->commit();
            header('Location: order_success.php?code=' . urlencode($orderCode));
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Pesanan belum dapat diproses. Silakan coba kembali.';
            error_log('Checkout PasarKita: ' . $e->getMessage());
        }
    }
}

$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';
function checkout_rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
?>
<section class="container py-5">
    <span class="eyebrow">SATU LANGKAH LAGI</span>
    <h1 class="detail-title mb-4">Checkout Pesanan</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3" style="color:#344e41">Informasi Pembeli</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label" for="customer_name">Nama lengkap</label><input id="customer_name" name="customer_name" class="form-control" maxlength="100" value="<?= htmlspecialchars($name) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="phone">Nomor telepon</label><input id="phone" name="phone" class="form-control" maxlength="20" inputmode="tel" value="<?= htmlspecialchars($phone) ?>" placeholder="Contoh: 081234567890" required></div>
                    <div class="mb-3"><label class="form-label" for="address">Alamat pengiriman</label><textarea id="address" name="address" class="form-control" rows="4" maxlength="2000" required><?= htmlspecialchars($address) ?></textarea></div>
                    <div class="p-3 rounded-3 mb-3" style="background:#f6f3ea">
                        <div class="fw-semibold" style="color:#344e41">Metode Pembayaran: COD</div>
                        <small class="text-muted">Pembayaran dilakukan saat pesanan diterima.</small>
                    </div>
                    <button class="btn btn-earth-primary w-100" type="submit">Konfirmasi Pesanan</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
                <h2 class="h5 fw-bold" style="color:#344e41">Ringkasan Pesanan</h2>
                <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                        <div><div class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></div><small class="text-muted"><?= (int)$item['quantity'] ?> × <?= checkout_rupiah($item['price']) ?></small></div>
                        <strong class="text-nowrap"><?= checkout_rupiah((float)$item['price'] * (int)$item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between mt-3"><strong>Total Pembayaran</strong><strong class="fs-5" style="color:#a66a4c"><?= checkout_rupiah($total) ?></strong></div>
                <a href="cart.php" class="btn btn-earth-outline w-100 mt-3">Kembali ke Keranjang</a>
            </div></div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
