<?php
require_once __DIR__ . '/bootstrap.php';
api_require_method('POST');

$data = api_read_json();
$name = trim((string) ($data['customer_name'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$address = trim((string) ($data['address'] ?? ''));
$paymentMethod = strtoupper(trim((string) ($data['payment_method'] ?? 'COD')));
$items = $data['items'] ?? null;

if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
    api_response(422, false, 'Nama pembeli wajib diisi dengan panjang 3 sampai 100 karakter.');
}
if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
    api_response(422, false, 'Nomor telepon tidak valid. Gunakan 8 sampai 20 karakter angka atau simbol telepon.');
}
if (mb_strlen($address) < 10 || mb_strlen($address) > 2000) {
    api_response(422, false, 'Alamat wajib diisi dengan panjang 10 sampai 2000 karakter.');
}
if ($paymentMethod !== 'COD') {
    api_response(422, false, 'Metode pembayaran yang didukung saat ini hanya COD.');
}
if (!is_array($items) || count($items) < 1 || count($items) > 50) {
    api_response(422, false, 'items harus berupa array berisi 1 sampai 50 produk.');
}

// Gabungkan jika ID produk yang sama dikirim lebih dari sekali.
$quantities = [];
foreach ($items as $item) {
    $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
    if ($productId === false || $quantity === false) {
        api_response(422, false, 'Setiap item harus memiliki product_id positif dan quantity antara 1 sampai 999.');
    }
    $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
    if ($quantities[$productId] > 999) {
        api_response(422, false, 'Jumlah produk tidak boleh melebihi 999 unit per produk.');
    }
}

try {
    $conn->begin_transaction();

    $selectProduct = $conn->prepare(
        'SELECT id, product_name, price, stock FROM products WHERE id = ? FOR UPDATE'
    );
    $orderItems = [];
    $grandTotal = 0.0;

    foreach ($quantities as $productId => $quantity) {
        $selectProduct->bind_param('i', $productId);
        $selectProduct->execute();
        $product = $selectProduct->get_result()->fetch_assoc();

        if (!$product) {
            throw new DomainException('Produk dengan ID ' . $productId . ' tidak ditemukan.');
        }
        if ((int) $product['stock'] < $quantity) {
            throw new DomainException('Stok produk ' . $product['product_name'] . ' tidak mencukupi. Stok tersedia: ' . (int) $product['stock'] . '.');
        }

        $price = (float) $product['price'];
        $subtotal = $price * $quantity;
        $grandTotal += $subtotal;
        $orderItems[] = [
            'product_id' => (int) $product['id'],
            'product_name' => $product['product_name'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ];
    }

    $orderCode = 'PK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $status = 'Menunggu Konfirmasi';
    $insertOrder = $conn->prepare(
        'INSERT INTO orders (order_code, customer_name, phone, address, total_amount, payment_method, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insertOrder->bind_param('ssssdss', $orderCode, $name, $phone, $address, $grandTotal, $paymentMethod, $status);
    $insertOrder->execute();
    $orderId = (int) $conn->insert_id;

    $insertItem = $conn->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $updateStock = $conn->prepare(
        'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
    );

    foreach ($orderItems as $item) {
        $productId = $item['product_id'];
        $productName = $item['product_name'];
        $price = $item['price'];
        $quantity = $item['quantity'];
        $subtotal = $item['subtotal'];

        $insertItem->bind_param('iisdid', $orderId, $productId, $productName, $price, $quantity, $subtotal);
        $insertItem->execute();

        $updateStock->bind_param('iii', $quantity, $productId, $quantity);
        $updateStock->execute();
        if ($updateStock->affected_rows !== 1) {
            throw new DomainException('Stok produk berubah saat pesanan diproses. Silakan ulangi request.');
        }
    }

    $conn->commit();
    api_response(201, true, 'Pesanan berhasil dibuat.', [
        'order_id' => $orderId,
        'order_code' => $orderCode,
        'customer_name' => $name,
        'payment_method' => $paymentMethod,
        'status' => $status,
        'total_amount' => $grandTotal,
        'items' => $orderItems,
    ]);
} catch (DomainException $e) {
    $conn->rollback();
    api_response(422, false, $e->getMessage());
} catch (Throwable $e) {
    $conn->rollback();
    error_log('API pesanan: ' . $e->getMessage());
    api_response(500, false, 'Pesanan gagal diproses karena terjadi kesalahan pada server.');
}
