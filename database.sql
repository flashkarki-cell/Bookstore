-- ============================================================
--  Online Bookstore - Database Schema
--  Course: BIT306CO Project-V | Year III Semester I
-- ============================================================

CREATE DATABASE IF NOT EXISTS online_bookstore;
USE online_bookstore;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- stored as bcrypt hash
    role        ENUM('customer','admin') DEFAULT 'customer',
    address     TEXT,
    phone       VARCHAR(15),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin account (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@bookstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ------------------------------------------------------------
-- 2. CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (category_name) VALUES
('Fiction'), ('Non-Fiction'), ('Science'), ('History'),
('Technology'), ('Children'), ('Biography'), ('Self-Help');

-- ------------------------------------------------------------
-- 3. BOOKS
-- ------------------------------------------------------------
CREATE TABLE books (
    book_id      INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    author       VARCHAR(150) NOT NULL,
    category_id  INT,
    price        DECIMAL(8,2) NOT NULL,
    stock        INT DEFAULT 0,
    description  TEXT,
    image        VARCHAR(255) DEFAULT 'default.jpg',
    isbn         VARCHAR(20),
    published_yr YEAR,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Sample books
INSERT INTO books (title, author, category_id, price, stock, description) VALUES
('The Alchemist',       'Paulo Coelho',      1, 350.00, 50, 'A journey of self-discovery.'),
('Sapiens',             'Yuval Noah Harari', 2, 480.00, 30, 'A brief history of humankind.'),
('Clean Code',          'Robert C. Martin',  5, 620.00, 20, 'A handbook of agile software craftsmanship.'),
('Wings of Fire',       'A.P.J. Abdul Kalam',8, 299.00, 40, 'Autobiography of Dr. Kalam.'),
('Harry Potter',        'J.K. Rowling',      1, 399.00, 60, 'The Philosopher\'s Stone.');

-- ------------------------------------------------------------
-- 4. CART
-- ------------------------------------------------------------
CREATE TABLE cart (
    cart_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    book_id    INT NOT NULL,
    quantity   INT DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, book_id)
);

-- ------------------------------------------------------------
-- 5. ORDERS
-- ------------------------------------------------------------
CREATE TABLE orders (
    order_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    total_amount  DECIMAL(10,2) NOT NULL,
    status        ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    payment_mode  ENUM('cod','online') DEFAULT 'cod',
    address       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 6. ORDER ITEMS
-- ------------------------------------------------------------
CREATE TABLE order_items (
    item_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    book_id     INT NOT NULL,
    quantity    INT NOT NULL,
    unit_price  DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id)  REFERENCES books(book_id)
);
