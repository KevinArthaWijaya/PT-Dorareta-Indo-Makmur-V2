-- ====================
-- DATABASE SETUP
-- ====================
CREATE DATABASE IF NOT EXISTS user_management;
USE user_management;

-- ====================
-- TABLE: Roles
-- ====================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- ====================
-- TABLE: Users
-- ====================
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

-- ====================
-- TABLE: Error Logs
-- ====================
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NULL,
    error_message TEXT NOT NULL,
    error_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ====================
-- TABLE: Categories
-- ====================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku_prefix VARCHAR(10) NOT NULL
);

-- ====================
-- TABLE: Units
-- ====================
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- ====================
-- TABLE: Products
-- ====================
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
    auto_created TINYINT(1) DEFAULT 0,
    highlight_color VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- ====================
-- TABLE: Suppliers
-- ====================
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

-- ====================
-- TABLE: Purchases
-- ====================
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    supplier_id INT,
    items INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'ordered', 'received') DEFAULT 'pending',
    grand_total INT NOT NULL DEFAULT 0,
    paid INT NOT NULL DEFAULT 0,
    due INT NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- ====================
-- TABLE: Purchase Items
-- ====================
CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT,
    product_id INT,
    product_name VARCHAR(255),
    quantity INT NOT NULL,
    unit_price INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ====================
-- TABLE: Purchase Returns
-- ====================
CREATE TABLE IF NOT EXISTS purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- ====================
-- TABLE: Purchase Return Items
-- ====================
CREATE TABLE IF NOT EXISTS purchase_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (return_id) REFERENCES purchase_returns(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ====================
-- TABLE: Customers
-- ====================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ====================
-- TABLE: Sales
-- ====================
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT,
    date DATE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    paid BIGINT NOT NULL DEFAULT 0,
    due BIGINT NOT NULL DEFAULT 0,
    grand_total BIGINT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- ====================
-- TABLE: Sales Items
-- ====================
CREATE TABLE IF NOT EXISTS sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit_price BIGINT NOT NULL,
    subtotal BIGINT NOT NULL DEFAULT 0,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ====================
-- TABLE: Sales Returns
-- ====================
CREATE TABLE IF NOT EXISTS sales_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    return_date DATE NOT NULL,
    total BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

-- ====================
-- TABLE: Sales Return Items
-- ====================
CREATE TABLE IF NOT EXISTS sales_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(100),
    quantity INT,
    unit_price BIGINT,
    subtotal BIGINT,
    FOREIGN KEY (return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ====================
-- TABLE: Sales Targets
-- ====================
CREATE TABLE IF NOT EXISTS sales_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_year VARCHAR(7) NOT NULL, -- format: '2024-06'
    target INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================
-- INITIAL DATA
-- ====================
INSERT INTO roles (role_name) VALUES 
('Admin'), 
('Manager'),
('Accounting'),
('Logistic');

INSERT INTO users (first_name, last_name, email, phone_number, username, password, role_id) 
VALUES (
    'John', 
    'Doe', 
    'admin@example.com', 
    '081234567890', 
    'admin', 
    '$2y$10$5nRUeM8h/jdmUO.BukmoKOzsVJeNkB4Q2aCcw2AsmhSqMGDMhdi3K', -- bcrypt hash of 'admin123'
    1
);

INSERT INTO categories (name, sku_prefix) VALUES
('Bata Tahan Api', 'BTA'),
('Semen Tahan Api', 'STA'),
('Panel Insulasi', 'PI'),
('Pemasangan', 'PS'),
('Pengiriman', 'PG');

INSERT INTO units (name) VALUES
('pcs'), ('roll'), ('meter'), ('zak'), ('kg'), ('unit');

INSERT INTO products 
(product_name, sku, category_id, unit_id, selling_price, minimal_purchase, stock_quantity, min_stock_warning, status, product_image) 
VALUES 
('Bata Tahan Api', 'SKU001', 1, 1, 15000, 12000, 500, 20, 'active', 'uploads/product_image/bata-tahan-api.jpg'),
('Semen Tahan Api', 'SKU002', 2, 4, 75000, 60000, 150, 10, 'active', 'uploads/product_image/semen-tahan-api.jpg'),
('Panel Insulasi Roll', 'SKU003', 3, 2, 50000, 40000, 80, 15, 'active', 'uploads/product_image/panel-insulasi-roll.jpg'),
('Pemasangan Bata Tahan Api', 'SKU004', 4, 6, 10000, 8000, 300, 30, 'active', 'uploads/product_image/pemasangan-bata.jpg'),
('Pengiriman Bata', 'SKU005', 5, 6, 25000, 20000, 100, 5, 'active', 'uploads/product_image/pengiriman-bata.jpg');
