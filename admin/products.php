<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
$result = $conn->query("SELECT p.id,p.product_name,p.price,p.stock,p.image,c.category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC");
$page_title = 'Kelola Produk';
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
<div><h1 class="admin-title h3 mb-1">Kelola Produk</h1><p class="text-muted mb-0">Tambah, ubah, dan hapus produk marketplace.</p></div>
<a href="product_add.php" class="btn btn-admin">+ Tambah Produk</a>
</div>
<?php if (!empty($_SESSION['flash'])): ?><div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<div class="card admin-card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Gambar</th><th>Nama Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead><tbody>
<?php if ($result->num_rows): while($p=$result->fetch_assoc()): ?>
<tr>
<td><?php $img=__DIR__.'/../uploads/'.($p['image']??''); if(!empty($p['image']) && is_file($img)): ?><img class="thumb" src="../uploads/<?= rawurlencode($p['image']) ?>" alt=""><?php else: ?><div class="thumb d-grid place-items-center text-center pt-2">✳</div><?php endif; ?></td>
<td class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></td><td><?= htmlspecialchars($p['category_name']) ?></td>
<td>Rp<?= number_format((float)$p['price'],0,',','.') ?></td><td><?= (int)$p['stock'] ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-outline-success" href="product_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
<form class="d-inline" method="post" action="product_delete.php" onsubmit="return confirm('Yakin ingin menghapus produk ini?')"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td>
</tr>
<?php endwhile; else: ?><tr><td colspan="6" class="text-center py-4">Belum ada produk.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
