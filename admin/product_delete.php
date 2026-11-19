<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: products.php');exit;}
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if($id && $id>0){
    // Jika produk pernah dipakai dalam pesanan, FK order_items akan menjadikan product_id NULL.
    // Item keranjang terkait akan terhapus melalui ON DELETE CASCADE.
    $stmt=$conn->prepare("SELECT image FROM products WHERE id=?");$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
    $del=$conn->prepare("DELETE FROM products WHERE id=?");$del->bind_param('i',$id);$del->execute();
    if($row && !empty($row['image'])){$path=__DIR__.'/../uploads/'.$row['image'];if(is_file($path))@unlink($path);}
    $_SESSION['flash']='Produk berhasil dihapus.';
}
header('Location: products.php');exit;
