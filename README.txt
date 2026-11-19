# PasarKita

Aplikasi Web Marketplace Tradisional yang dibangun menggunakan PHP Native, MySQLi, dan Bootstrap.

## Deskripsi
PasarKita merupakan aplikasi marketplace sederhana yang memungkinkan pembeli melihat produk kebutuhan sehari-hari, memasukkan produk ke keranjang, dan melakukan pemesanan menggunakan metode pembayaran Cash on Delivery (COD).

## Fitur
- Menampilkan daftar produk.
- Melihat detail produk.
- Keranjang belanja.
- Checkout dengan metode COD.
- Menyimpan transaksi ke database.
- Login administrator.
- CRUD produk dan kategori.
- Pengelolaan dan konfirmasi pesanan.

## Teknologi
- PHP Native
- MySQL
- MySQLi
- Bootstrap
- HTML, CSS, JavaScript

## Demo
https://pasarkita.ct.ws/

## Administrator
Halaman login administrator dapat diakses melalui `/admin/login.php`.

## Instalasi Lokal
1. Clone repository.
2. Pindahkan folder project ke direktori `htdocs` XAMPP.
3. Buat database bernama `pasarkita`.
4. Import struktur database.
5. Salin `config/database.local.php.example` menjadi `config/database.local.php`.
6. Sesuaikan konfigurasi database lokal.
7. Jalankan melalui `http://localhost/pasarkita/`.

## Catatan Keamanan
File kredensial database tidak disertakan dalam repository.