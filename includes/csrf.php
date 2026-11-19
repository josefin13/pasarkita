<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
}
