-- Database: it_inventory
CREATE DATABASE IF NOT EXISTS it_inventory DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_inventory;

-- 1. Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'USER',
    image VARCHAR(255) DEFAULT 'default_user.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table: products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    spec TEXT,
    price DECIMAL(10, 2) DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'ชิ้น',
    min_alert INT DEFAULT 5,
    image VARCHAR(255) DEFAULT 'default_product.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table: product_serials
CREATE TABLE IF NOT EXISTS product_serials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    serial_code VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('available', 'borrowed', 'repairing', 'broken', 'lost') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 4. Table: borrowings
CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_id INT NOT NULL,
    borrower_id INT NOT NULL,
    asset_number VARCHAR(100),
    building VARCHAR(100),
    floor VARCHAR(100),
    department VARCHAR(100),
    approver_name VARCHAR(255),
    reason TEXT,
    notes TEXT,
    image VARCHAR(255),
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at TIMESTAMP NULL,
    FOREIGN KEY (serial_id) REFERENCES product_serials(id) ON DELETE CASCADE,
    FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Table: stock_imports
CREATE TABLE IF NOT EXISTS stock_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Table: stock_import_items
CREATE TABLE IF NOT EXISTS stock_import_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT DEFAULT 0,
    FOREIGN KEY (import_id) REFERENCES stock_imports(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. Table: stock_import_serials
CREATE TABLE IF NOT EXISTS stock_import_serials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_item_id INT NOT NULL,
    serial_code VARCHAR(100) NOT NULL,
    FOREIGN KEY (import_item_id) REFERENCES stock_import_items(id) ON DELETE CASCADE
);

-- 8. Table: categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

-- 8.5. Table: product_types
CREATE TABLE IF NOT EXISTS product_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

-- 9. Table: units
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

-- Default Admin Account (password: admin123)
INSERT INTO users (fullname, email, password, role) VALUES 
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');
