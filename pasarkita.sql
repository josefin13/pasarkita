-- =========================================================
-- DATABASE PASARKITA
-- Marketplace kebutuhan pasar tradisional
-- DBMS: MySQL / MariaDB (XAMPP)
-- =========================================================

CREATE DATABASE IF NOT EXISTS pasarkita
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pasarkita;

-- ---------------------------------------------------------
-- 1. USERS: akun administrator
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 2. CATEGORIES: kategori produk
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. PRODUCTS: data produk
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. CARTS: keranjang berdasarkan session pembeli
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS carts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. CART_ITEMS: detail produk di keranjang
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT uq_cart_product UNIQUE (cart_id, product_id),
    CONSTRAINT fk_cart_items_cart
        FOREIGN KEY (cart_id) REFERENCES carts(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_cart_quantity CHECK (quantity > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 6. ORDERS: data utama pemesanan
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'COD',
    status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Konfirmasi',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_orders_total CHECK (total_amount >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 7. ORDER_ITEMS: rincian produk pada pesanan
-- product_name dan price menyimpan snapshot saat checkout.
-- product_id dibuat NULL jika produk dihapus di kemudian hari,
-- sehingga riwayat transaksi tetap tersimpan.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_order_items_price CHECK (price >= 0),
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_items_subtotal CHECK (subtotal >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DATA AWAL: kategori
-- ---------------------------------------------------------
INSERT IGNORE INTO categories (category_name, description) VALUES
('Sayuran', 'Sayuran segar untuk kebutuhan sehari-hari'),
('Buah-buahan', 'Berbagai macam buah segar'),
('Sembako', 'Beras dan kebutuhan pokok lainnya'),
('Ikan', 'Ikan dan hasil perikanan'),
('Bumbu Dapur', 'Bumbu dan rempah untuk memasak');

-- ---------------------------------------------------------
-- DATA AWAL: produk contoh
-- Harga dan stok hanya data simulasi untuk pengembangan.
-- ---------------------------------------------------------
INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Bayam', 'Bayam segar pilihan, dijual per ikat.', 5000, 30, 'bayam.jpg'
FROM categories c WHERE c.category_name = 'Sayuran'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Bayam');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Wortel', 'Wortel segar, dijual per kilogram.', 12000, 25, 'wortel.jpg'
FROM categories c WHERE c.category_name = 'Sayuran'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Wortel');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Tomat', 'Tomat merah segar, dijual per kilogram.', 10000, 20, 'tomat.jpg'
FROM categories c WHERE c.category_name = 'Sayuran'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Tomat');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Apel', 'Apel segar, dijual per kilogram.', 25000, 18, 'apel.jpg'
FROM categories c WHERE c.category_name = 'Buah-buahan'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Apel');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Pisang', 'Pisang matang pilihan, dijual per sisir.', 18000, 15, 'pisang.jpg'
FROM categories c WHERE c.category_name = 'Buah-buahan'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Pisang');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Beras Premium', 'Beras premium, kemasan 5 kilogram.', 75000, 20, 'beras.jpg'
FROM categories c WHERE c.category_name = 'Sembako'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Beras Premium');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Gula Pasir', 'Gula pasir putih, dijual per kilogram.', 17000, 25, 'gula.jpg'
FROM categories c WHERE c.category_name = 'Sembako'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Gula Pasir');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Ikan Nila', 'Ikan nila segar, dijual per kilogram.', 32000, 12, 'nila.jpg'
FROM categories c WHERE c.category_name = 'Ikan'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Ikan Nila');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Cabai Merah', 'Cabai merah segar, dijual per kilogram.', 35000, 10, 'cabai.jpg'
FROM categories c WHERE c.category_name = 'Bumbu Dapur'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Cabai Merah');

INSERT INTO products
(category_id, product_name, description, price, stock, image)
SELECT c.id, 'Bawang Merah', 'Bawang merah, dijual per kilogram.', 30000, 15, 'bawang.jpg'
FROM categories c WHERE c.category_name = 'Bumbu Dapur'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_name = 'Bawang Merah');

-- Catatan:
-- Akun admin akan dibuat pada tahap autentikasi menggunakan PHP password_hash().
-- Jangan menyimpan password admin dalam bentuk teks biasa.
