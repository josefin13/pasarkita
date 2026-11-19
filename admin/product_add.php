<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/product_form.php';
$error = '';
$name = ''; $description = ''; $price = ''; $stock = ''; $category_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    if ($name === '' || !is_numeric($price) || (float)$price < 0 || filter_var($stock,FILTER_VALIDATE_INT) === false || (int)$stock < 0 || (int)$category_id < 1) {
        $error = 'Periksa kembali nama, kategori, harga, dan stok produk.';
    } else {
        try {
            $image = upload_product_image('image');
            $stmt = $conn->prepare("INSERT INTO products (category_id,product_name,description,price,stock,image) VALUES (?,?,?,?,?,?)");
            $cat=(int)$category_id; $pr=(float)$price; $st=(int)$stock;
            $stmt->bind_param('issdis',$cat,$name,$description,$pr,$st,$image);
            // image boleh NULL; bind_param string kosong lebih konsisten untuk kolom nullable.
            $stmt->execute();
            $_SESSION['flash'] = 'Produk berhasil ditambahkan.';
            header('Location: products.php'); exit;
        } catch (Throwable $e) { $error = $e instanceof RuntimeException ? $e->getMessage() : 'Produk gagal disimpan. Periksa data dan koneksi database.'; }
    }
}
$page_title='Tambah Produk';
require_once __DIR__.'/includes/header.php';
?>
<h1 class="admin-title h3">Tambah Produk</h1><p class="text-muted">Masukkan informasi produk baru.</p>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card admin-card"><div class="card-body p-4">
<form method="post" enctype="multipart/form-data">
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Nama Produk</label><input name="product_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($name) ?>" required></div>
<div class="col-md-4"><label class="form-label">Kategori</label><select name="category_id" class="form-select" required><option value="">Pilih kategori</option><?php while($c=$categories->fetch_assoc()): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$category_id===(string)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option><?php endwhile; ?></select></div>
<div class="col-md-6"><label class="form-label">Harga (Rp)</label><input type="number" name="price" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars((string)$price) ?>" required></div>
<div class="col-md-6"><label class="form-label">Stok</label><input type="number" name="stock" class="form-control" min="0" step="1" value="<?= htmlspecialchars((string)$stock) ?>" required></div>
<div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea></div>
<div class="col-12"><label class="form-label">Foto Produk (JPG/PNG/WEBP, maks. 2 MB)</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
</div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-admin">Simpan Produk</button><a href="products.php" class="btn btn-outline-secondary">Batal</a></div>
</form></div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
