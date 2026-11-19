<?php
// File helper untuk form tambah dan edit produk.
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name");
function upload_product_image(string $field, ?string $oldImage = null): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return $oldImage;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gambar gagal.');
    if ($_FILES[$field]['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$field]['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $newName = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $newName)) throw new RuntimeException('Gagal menyimpan gambar.');
    if ($oldImage && is_file($uploadDir . $oldImage)) @unlink($uploadDir . $oldImage);
    return $newName;
}
?>
