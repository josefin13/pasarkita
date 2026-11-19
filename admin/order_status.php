<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $token)) {
    http_response_code(403);
    exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['new_status'] ?? '';
$allowed = ['Diproses', 'Selesai', 'Dibatalkan'];

if (!$orderId || $orderId < 1 || !in_array($newStatus, $allowed, true)) {
    $_SESSION['flash_error'] = 'Data perubahan status tidak valid.';
    header('Location: orders.php');
    exit;
}

try {
    $conn->begin_transaction();

    $orderStmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? FOR UPDATE");
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    $current = $order['status'];
    $validTransition =
        ($current === 'Menunggu Konfirmasi' && in_array($newStatus, ['Diproses', 'Dibatalkan'], true)) ||
        ($current === 'Diproses' && in_array($newStatus, ['Selesai', 'Dibatalkan'], true));

    if (!$validTransition) {
        throw new RuntimeException('Perubahan status tersebut tidak diperbolehkan.');
    }

    // Jika dibatalkan, kembalikan stok setiap produk yang tercatat pada detail pesanan.
    if ($newStatus === 'Dibatalkan') {
        $itemsStmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ? FOR UPDATE");
        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();

        $restore = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        while ($item = $itemsResult->fetch_assoc()) {
            if ($item['product_id'] !== null) {
                $qty = (int)$item['quantity'];
                $productId = (int)$item['product_id'];
                $restore->bind_param('ii', $qty, $productId);
                $restore->execute();
            }
        }
    }

    $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->bind_param('si', $newStatus, $orderId);
    $update->execute();

    $conn->commit();
    $_SESSION['flash'] = 'Status pesanan berhasil diubah menjadi ' . $newStatus . '.';
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Status pesanan gagal diperbarui.';
    error_log('Perubahan status pesanan PasarKita: ' . $e->getMessage());
}

header('Location: order_detail.php?id=' . (int)$orderId);
exit;
