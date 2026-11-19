<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

$sid = session_id();
$cartStmt = $conn->prepare("SELECT id FROM carts WHERE session_id = ? LIMIT 1");
$cartStmt->bind_param('s', $sid);
$cartStmt->execute();
$cartRow = $cartStmt->get_result()->fetch_assoc();
$cart_id = $cartRow ? (int)$cartRow['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        if (!$product_id || !$quantity || $quantity < 1) {
            $_SESSION['cart_error'] = 'Produk atau jumlah yang dipilih tidak valid.';
            header('Location: index.php'); exit;
        }

        $productStmt = $conn->prepare("SELECT id, product_name, stock FROM products WHERE id = ? LIMIT 1");
        $productStmt->bind_param('i', $product_id);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();

        if (!$product) {
            $_SESSION['cart_error'] = 'Produk tidak ditemukan.';
            header('Location: index.php'); exit;
        }
        if ((int)$product['stock'] < 1) {
            $_SESSION['cart_error'] = 'Maaf, stok produk sedang habis.';
            header('Location: detail.php?id=' . $product_id); exit;
        }

        if (!$cart_id) {
            $insertCart = $conn->prepare("INSERT INTO carts (session_id) VALUES (?)");
            $insertCart->bind_param('s', $sid);
            $insertCart->execute();
            $cart_id = (int)$conn->insert_id;
        }

        $existingStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
        $existingStmt->bind_param('ii', $cart_id, $product_id);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        $newQty = $quantity + ($existing ? (int)$existing['quantity'] : 0);

        if ($newQty > (int)$product['stock']) {
            $_SESSION['cart_error'] = 'Jumlah melebihi stok yang tersedia (' . (int)$product['stock'] . ').';
            header('Location: detail.php?id=' . $product_id); exit;
        }

        if ($existing) {
            $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update->bind_param('ii', $newQty, $existing['id']);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->bind_param('iii', $cart_id, $product_id, $quantity);
            $insert->execute();
        }
        $_SESSION['cart_success'] = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') . ' berhasil ditambahkan ke keranjang.';
        header('Location: cart.php'); exit;
    }

    if (!$cart_id) {
        header('Location: cart.php'); exit;
    }

    if ($action === 'update') {
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        if ($item_id && $quantity && $quantity > 0) {
            $check = $conn->prepare(
                "SELECT ci.id, p.stock FROM cart_items ci
                 JOIN products p ON p.id = ci.product_id
                 WHERE ci.id = ? AND ci.cart_id = ?"
            );
            $check->bind_param('ii', $item_id, $cart_id);
            $check->execute();
            $item = $check->get_result()->fetch_assoc();
            if ($item && $quantity <= (int)$item['stock']) {
                $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?");
                $update->bind_param('iii', $quantity, $item_id, $cart_id);
                $update->execute();
                $_SESSION['cart_success'] = 'Jumlah produk berhasil diperbarui.';
            } elseif ($item) {
                $_SESSION['cart_error'] = 'Jumlah melebihi stok yang tersedia.';
            }
        }
        header('Location: cart.php'); exit;
    }

    if ($action === 'remove') {
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        if ($item_id) {
            $delete = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $delete->bind_param('ii', $item_id, $cart_id);
            $delete->execute();
            $_SESSION['cart_success'] = 'Produk dihapus dari keranjang.';
        }
        header('Location: cart.php'); exit;
    }
}

$items = [];
$total = 0;
if ($cart_id) {
    $itemsStmt = $conn->prepare(
        "SELECT ci.id AS cart_item_id, ci.quantity, p.id AS product_id,
                p.product_name, p.price, p.stock, p.image
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.cart_id = ?
         ORDER BY ci.id DESC"
    );
    $itemsStmt->bind_param('i', $cart_id);
    $itemsStmt->execute();
    $result = $itemsStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = (float)$row['price'] * (int)$row['quantity'];
        $total += $row['subtotal'];
        $items[] = $row;
    }
}

$page_title = 'Keranjang Belanja';
require_once __DIR__ . '/includes/header.php';
function cart_rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
?>
<section class="container py-5">
    <span class="eyebrow">BELANJAANMU</span>
    <h1 class="detail-title mb-4">Keranjang Belanja</h1>

    <?php if (!empty($_SESSION['cart_success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['cart_error']); unset($_SESSION['cart_error']); ?></div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <h3>Keranjangmu masih kosong</h3>
            <p>Yuk, temukan kebutuhan harian yang kamu perlukan.</p>
            <a href="index.php#produk" class="btn btn-earth-primary">Mulai Belanja</a>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th class="ps-3">Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2" style="min-width:160px">
                                    <?php $imgPath = __DIR__ . '/uploads/' . ($item['image'] ?? ''); ?>
                                    <?php if (!empty($item['image']) && is_file($imgPath)): ?>
                                        <img src="uploads/<?= rawurlencode($item['image']) ?>" alt="" style="width:55px;height:48px;object-fit:cover;border-radius:8px">
                                    <?php else: ?><div class="rounded-3 d-grid place-items-center" style="width:55px;height:48px;background:#eef0e4;color:#588157">✳</div><?php endif; ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-nowrap"><?= cart_rupiah($item['price']) ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-1 align-items-center">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['cart_item_id'] ?>">
                                        <input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" value="<?= (int)$item['quantity'] ?>" class="form-control form-control-sm" style="width:70px">
                                        <button class="btn btn-sm btn-outline-success" title="Perbarui jumlah">↻</button>
                                    </form>
                                </td>
                                <td class="text-nowrap fw-semibold"><?= cart_rupiah($item['subtotal']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Hapus produk ini dari keranjang?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['cart_item_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">×</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold" style="color:#344e41">Ringkasan Belanja</h2>
                    <div class="d-flex justify-content-between my-3"><span class="text-muted">Subtotal</span><strong><?= cart_rupiah($total) ?></strong></div>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Pembayaran</span><strong>COD</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4"><strong>Total</strong><strong class="fs-5" style="color:#a66a4c"><?= cart_rupiah($total) ?></strong></div>
                    <a href="checkout.php" class="btn btn-earth-primary w-100">Lanjut Checkout</a>
                    <a href="index.php#produk" class="btn btn-earth-outline w-100 mt-2">Tambah Produk Lain</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
