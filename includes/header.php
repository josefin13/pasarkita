<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = $page_title ?? 'PasarKita';
$cart_count = 0;

if (isset($conn) && $conn instanceof mysqli) {
    $sid = session_id();
    $stmtCartCount = $conn->prepare(
        "SELECT COALESCE(SUM(ci.quantity), 0) AS total
         FROM carts c
         LEFT JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.session_id = ?"
    );
    $stmtCartCount->bind_param('s', $sid);
    $stmtCartCount->execute();
    $cart_count = (int)$stmtCartCount->get_result()->fetch_assoc()['total'];
    $stmtCartCount->close();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> | PasarKita</title>
    <meta name="description" content="PasarKita - belanja kebutuhan harian dengan mudah.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-pasarkita sticky-top">
    <div class="container">
        <a class="navbar-brand brand-mark" href="index.php">
            <span class="brand-icon">P</span> Pasar<span>Kita</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#produk">Produk</a></li>
                <li class="nav-item">
                    <a class="btn btn-earth-outline btn-sm ms-lg-2" href="cart.php">
                        Keranjang <span class="cart-count"><?= $cart_count ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main>
