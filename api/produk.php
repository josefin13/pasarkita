<?php
require_once __DIR__ . '/bootstrap.php';
api_require_method('GET');

try {
    if (isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            api_response(400, false, 'Parameter id harus berupa bilangan bulat positif.');
        }

        $stmt = $conn->prepare(
            'SELECT p.id, p.product_name, p.description, p.price, p.stock, p.image,
                    p.category_id, c.category_name
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product) {
            api_response(404, false, 'Produk dengan ID tersebut tidak ditemukan.');
        }

        $product['id'] = (int) $product['id'];
        $product['category_id'] = (int) $product['category_id'];
        $product['price'] = (float) $product['price'];
        $product['stock'] = (int) $product['stock'];
        api_response(200, true, 'Detail produk berhasil diambil.', $product);
    }

    $result = $conn->query(
        'SELECT p.id, p.product_name, p.description, p.price, p.stock, p.image,
                p.category_id, c.category_name
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         ORDER BY p.id ASC'
    );

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['price'] = (float) $row['price'];
        $row['stock'] = (int) $row['stock'];
        $products[] = $row;
    }

    api_response(200, true, 'Daftar produk berhasil diambil.', $products);
} catch (Throwable $e) {
    error_log('API produk: ' . $e->getMessage());
    api_response(500, false, 'Terjadi kesalahan pada server saat mengambil data produk.');
}
