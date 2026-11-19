<?php
// Konfigurasi dasar untuk seluruh endpoint REST API PasarKita.
header('Content-Type: application/json; charset=utf-8');

function api_response(int $status, bool $success, string $message, $data = null): void
{
    http_response_code($status);
    $response = [
        'success' => $success,
        'message' => $message,
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        header('Allow: ' . strtoupper($method));
        api_response(405, false, 'Metode HTTP tidak diizinkan. Gunakan ' . strtoupper($method) . '.');
    }
}

function api_read_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_response(400, false, 'Format JSON tidak valid atau body request kosong.');
    }
    return $data;
}

// Menggunakan koneksi database yang sama dengan aplikasi web PasarKita.
require_once __DIR__ . '/../config/database.php';
