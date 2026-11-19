<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/product_form.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id || $id<1){header('Location: products.php');exit;}
$stmt=$conn->prepare("SELECT * FROM products WHERE id=?");$stmt->bind_param('i',$id);$stmt->execute();$product=$stmt->get_result()->fetch_assoc();
if(!$product){header('Location: products.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['product_name']??'');$description=trim($_POST['description']??'');
    $price=$_POST['price']??'';$stock=$_POST['stock']??'';$category_id=$_POST['category_id']??'';
    if($name===''||!is_numeric($price)||(float)$price<0||filter_var($stock,FILTER_VALIDATE_INT)===false||(int)$stock<0||(int)$category_id<1){
        $error='Periksa kembali nama, kategori, harga, dan stok produk.';
    }else{
        try{
            $image=upload_product_image('image',$product['image']);
            $cat=(int)$category_id;$pr=(float)$price;$st=(int)$stock;
            $up=$conn->prepare("UPDATE products SET category_id=?,product_name=?,description=?,price=?,stock=?,image=? WHERE id=?");
            $up->bind_param('issdisi',$cat,$name,$description,$pr,$st,$image,$id);$up->execute();
            $_SESSION['flash']='Produk berhasil diperbarui.';header('Location: products.php');exit;
        }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Produk gagal diperbarui.';}
    }
    $product=array_merge($product,['product_name'=>$name,'description'=>$description,'price'=>$price,'stock'=>$stock,'category_id'=>$category_id]);
}
$page_title='Edit Produk';require_once __DIR__.'/includes/header.php';
?>
<h1 class="admin-title h3">Edit Produk</h1><p class="text-muted">Perbarui informasi produk.</p>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card admin-card"><div class="card-body p-4">
<form method="post" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-8"><label class="form-label">Nama Produk</label><input name="product_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($product['product_name']) ?>" required></div>
<div class="col-md-4"><label class="form-label">Kategori</label><select name="category_id" class="form-select" required><?php while($c=$categories->fetch_assoc()): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id']===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option><?php endwhile; ?></select></div>
<div class="col-md-6"><label class="form-label">Harga (Rp)</label><input type="number" name="price" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars((string)$product['price']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Stok</label><input type="number" name="stock" class="form-control" min="0" value="<?= (int)$product['stock'] ?>" required></div>
<div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']??'') ?></textarea></div>
<div class="col-12"><label class="form-label">Ganti Foto Produk (opsional)</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">Kosongkan jika tidak ingin mengganti foto.</div></div>
</div><div class="mt-4 d-flex gap-2"><button class="btn btn-admin">Simpan Perubahan</button><a href="products.php" class="btn btn-outline-secondary">Batal</a></div></form>
</div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
