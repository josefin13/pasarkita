<?php
/**
 * Koneksi database PasarKita.
 * Buat file config/database.local.php berdasarkan database.local.php.example.
 * File database.local.php tidak boleh dimasukkan ke repository publik.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$localConfig = __DIR__ . '/database.local.php';
if (!is_file($localConfig)) {
    http_response_code(500);
    exit('Konfigurasi database belum tersedia. Salin config/database.local.php.example menjadi config/database.local.php dan isi kredensial database.');
}

$dbConfig = require $localConfig;
$requiredKeys = ['host', 'username', 'password', 'database'];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $dbConfig)) {
        http_response_code(500);
        exit('Konfigurasi database tidak lengkap.');
    }
}

try {
    $conn = new mysqli(
        (string)$dbConfig['host'],
        (string)$dbConfig['username'],
        (string)$dbConfig['password'],
        (string)$dbConfig['database']
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Koneksi database PasarKita gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
