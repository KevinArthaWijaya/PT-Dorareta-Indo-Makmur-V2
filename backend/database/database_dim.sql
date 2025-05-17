-- Buat Database
CREATE DATABASE IF NOT EXISTS user_management;
USE user_management;

-- Tabel Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Data Role Awal
INSERT INTO roles (role_name) VALUES 
('Admin'), 
('Manager'),
('Accounting'),
('Logistic');

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    profile_image VARCHAR(255),
    bio TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Admin Awal (username: admin, password: admin123)
INSERT INTO users (first_name, last_name, email, phone_number, username, password, role_id) 
VALUES (
    'John', 
    'Doe', 
    'admin@example.com', 
    '081234567890', 
    'admin', 
    '$2y$10$5nRUeM8h/jdmUO.BukmoKOzsVJeNkB4Q2aCcw2AsmhSqMGDMhdi3K', -- password: admin123 (bcrypt)
    1
);

-- Tabel Error Logs
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NULL,
    error_message TEXT NOT NULL,
    error_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Tabel Units
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Tabel Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    category_id INT,
    unit_id INT,
    selling_price INT NOT NULL,
    minimal_purchase INT NOT NULL,
    stock_quantity INT NOT NULL,
    min_stock_warning INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    product_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

ALTER TABLE products
ADD COLUMN auto_created TINYINT(1) DEFAULT 0,
ADD COLUMN highlight_color VARCHAR(20) DEFAULT NULL;

-- Data Awal Category
INSERT INTO categories (name) VALUES
('Bata Tahan Api'),
('Semen Tahan Api'),
('Panel Insulasi'),
('Pemasangan'),
('Pengiriman');

-- Data Awal Unit
INSERT INTO units (name) VALUES
('pcs'), ('roll'), ('meter'), ('zak'), ('kg'), ('unit');

-- Data Awal Product
INSERT INTO products 
(product_name, sku, category_id, unit_id, selling_price, minimal_purchase, stock_quantity, min_stock_warning, status, product_image) 
VALUES 
('Bata Tahan Api', 'SKU001', 1, 1, 15000, 12000, 500, 20, 'active', 'uploads/product_image/bata-tahan-api.jpg'),
('Semen Tahan Api', 'SKU002', 2, 4, 75000, 60000, 150, 10, 'active', 'uploads/product_image/semen-tahan-api.jpg'),
('Panel Insulasi Roll', 'SKU003', 3, 2, 50000, 40000, 80, 15, 'active', 'uploads/product_image/panel-insulasi-roll.jpg'),
('Pemasangan Bata Tahan Api', 'SKU004', 4, 6, 10000, 8000, 300, 30, 'active', 'uploads/product_image/pemasangan-bata.jpg'),
('Pengiriman Bata', 'SKU005', 5, 6, 25000, 20000, 100, 5, 'active', 'uploads/product_image/pengiriman-bata.jpg');

-- Tabel Supplier (karena input manual, maka hanya nama & kontak dasar)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,           -- nomor faktur pembelian
    date DATE NOT NULL,                               -- tanggal pembelian
    supplier_id INT,                                  -- relasi ke supplier
    items INT NOT NULL DEFAULT 0,                     -- jumlah total item dalam pembelian (jumlah baris di purchase_items)
    status ENUM('pending', 'ordered', 'received') DEFAULT 'pending',  -- status pengiriman
    grand_total INT NOT NULL DEFAULT 0,               -- total harga seluruh item
    paid INT NOT NULL DEFAULT 0,                      -- yang sudah dibayar
    due INT NOT NULL DEFAULT 0,                       -- sisa pembayaran (grand_total - paid)
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid', -- status pembayaran
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);


-- Tabel Detail Item Pembelian
CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT,
    product_id INT,
    product_name VARCHAR(255), -- disimpan juga untuk histori
    quantity INT NOT NULL,
    unit_price INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Tabel Retur Pembelian
CREATE TABLE IF NOT EXISTS purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE `purchase_returns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL
);

CREATE TABLE `purchase_return_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `return_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL
);

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(50) NOT NULL UNIQUE,
  date DATE NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
  paid BIGINT NOT NULL DEFAULT 0,
  due BIGINT NOT NULL DEFAULT 0,
  grand_total BIGINT NOT NULL DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  unit_price BIGINT NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

CREATE TABLE sales_returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  return_date DATE NOT NULL,
  total BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

CREATE TABLE sales_return_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  return_id INT NOT NULL,
  product_name VARCHAR(100),
  quantity INT,
  unit_price BIGINT,
  FOREIGN KEY (return_id) REFERENCES sales_returns(id) ON DELETE CASCADE
);

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  email VARCHAR(100),
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- View Cek Isi Awal
SELECT * FROM roles;
SELECT * FROM users;
SELECT * FROM error_logs ORDER BY error_time DESC;

SELECT id, name, status FROM products;
UPDATE products SET status = 'inactive' WHERE status IS NULL;

ALTER TABLE sales
ADD customer_id INT AFTER invoice_no;

ALTER TABLE sales
ADD CONSTRAINT fk_sales_customer
FOREIGN KEY (customer_id) REFERENCES customers(id);

ALTER TABLE sales_items ADD COLUMN product_id INT AFTER sale_id;

ALTER TABLE sales_items
ADD CONSTRAINT fk_sales_items_product
FOREIGN KEY (product_id) REFERENCES products(id);

ALTER TABLE sales_items ADD COLUMN subtotal BIGINT NOT NULL DEFAULT 0 AFTER unit_price;

ALTER TABLE sales_return_items ADD COLUMN product_id INT AFTER return_id;
ALTER TABLE sales_return_items ADD FOREIGN KEY (product_id) REFERENCES products(id);

ALTER TABLE sales_return_items ADD COLUMN subtotal BIGINT AFTER unit_price;

ALTER TABLE suppliers ADD COLUMN updated_at DATETIME NULL AFTER created_at;

ALTER TABLE suppliers
  ADD COLUMN phone VARCHAR(20) AFTER name,
  ADD COLUMN email VARCHAR(100) AFTER phone,
  ADD COLUMN address TEXT AFTER email,
  DROP COLUMN contact_info;

  CREATE TABLE sales_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_year VARCHAR(7) NOT NULL, -- format: '2024-06'
    target INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE categories
ADD COLUMN sku_prefix VARCHAR(10) NOT NULL AFTER name;