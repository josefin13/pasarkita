<?php
$page_title = $page_title ?? 'Dashboard';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> | Admin PasarKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body.admin-body{background:#f6f3ea}
        .admin-sidebar{background:#344e41;min-height:calc(100vh - 64px)}
        .admin-sidebar a{display:block;color:#e9eee6;text-decoration:none;padding:10px 13px;border-radius:8px;margin-bottom:5px}
        .admin-sidebar a:hover,.admin-sidebar a.active{background:#588157;color:#fff}
        .admin-top{background:#fff;border-bottom:1px solid #e7e0d0}
        .admin-card{border:1px solid #e7e0d0;border-radius:14px}
        .admin-title{color:#344e41;font-weight:800}
        .btn-admin{background:#344e41;color:#fff;border-color:#344e41}
        .btn-admin:hover{background:#76523b;color:#fff;border-color:#76523b}
        .table thead th{background:#eef0e4;color:#344e41;white-space:nowrap}
        .thumb{width:58px;height:48px;object-fit:cover;border-radius:8px;background:#eef0e4}
    </style>
</head>
<body class="admin-body">
<nav class="navbar admin-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" style="color:#344e41" href="dashboard.php">PasarKita <span class="text-muted fw-normal">/ Admin</span></a>
    <div class="d-flex align-items-center gap-3">
      <span class="small text-muted">Halo, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-secondary">Keluar</a>
    </div>
  </div>
</nav>
<div class="container-fluid">
 <div class="row">
  <aside class="col-md-2 col-lg-2 admin-sidebar p-3">
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Kelola Produk</a>
    <a href="categories.php">Kategori</a>
    <a href="orders.php">Pesanan</a>
    <a href="../index.php" target="_blank">Lihat Website ↗</a>
  </aside>
  <main class="col-md-10 col-lg-10 p-4">
