<?php
require_once __DIR__ . '/bootstrap.php';
api_require_method('GET');

try {
    $result = $conn->query(
        'SELECT c.id, c.category_name, c.description, COUNT(p.id) AS total_produk
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id, c.category_name, c.description
         ORDER BY c.id ASC'
    );

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['total_produk'] = (int) $row['total_produk'];
        $categories[] = $row;
    }

    api_response(200, true, 'Daftar kategori berhasil diambil.', $categories);
} catch (Throwable $e) {
    error_log('API kategori: ' . $e->getMessage());
    api_response(500, false, 'Terjadi kesalahan pada server saat mengambil data kategori.');
}
