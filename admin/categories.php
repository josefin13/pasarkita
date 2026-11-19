<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['category_name']??'');$desc=trim($_POST['description']??'');
    if($name==='')$error='Nama kategori wajib diisi.';
    else{
        try{$stmt=$conn->prepare("INSERT INTO categories(category_name,description) VALUES(?,?)");$stmt->bind_param('ss',$name,$desc);$stmt->execute();$_SESSION['flash']='Kategori berhasil ditambahkan.';header('Location: categories.php');exit;}
        catch(Throwable $e){$error='Kategori gagal ditambahkan. Pastikan nama kategori belum digunakan.';}
    }
}
$categories=$conn->query("SELECT c.id,c.category_name,c.description,COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.category_name");
$page_title='Kategori';require_once __DIR__.'/includes/header.php';
?>
<h1 class="admin-title h3">Kategori Produk</h1><p class="text-muted">Lihat kategori dan tambahkan kategori baru.</p>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if(!empty($_SESSION['flash'])): ?><div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']);unset($_SESSION['flash']); ?></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-7"><div class="card admin-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Kategori</th><th>Deskripsi</th><th>Jumlah Produk</th></tr></thead><tbody><?php while($c=$categories->fetch_assoc()): ?><tr><td class="fw-semibold"><?= htmlspecialchars($c['category_name']) ?></td><td><?= htmlspecialchars($c['description']??'') ?></td><td><?= (int)$c['product_count'] ?></td></tr><?php endwhile; ?></tbody></table></div></div></div>
<div class="col-lg-5"><div class="card admin-card"><div class="card-body"><h2 class="h5 admin-title">Tambah Kategori</h2><form method="post"><div class="mb-3"><label class="form-label">Nama kategori</label><input name="category_name" class="form-control" maxlength="100" required></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="3"></textarea></div><button class="btn btn-admin">Simpan Kategori</button></form></div></div></div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
