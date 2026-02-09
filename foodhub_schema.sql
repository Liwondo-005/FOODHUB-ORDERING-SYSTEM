-- FoodHub Database Schema
-- Normalized to Third Normal Form (3NF)
-- Tables: users, restaurants, categories, menu_items, carts, cart_items, orders, order_items, payments

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT,
  `role` ENUM('customer', 'owner', 'admin') DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 2. RESTAURANTS TABLE
-- ============================================
CREATE TABLE `restaurants` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `cuisine` VARCHAR(100),
  `area` VARCHAR(100),
  `owner_id` INT NOT NULL UNIQUE,
  `rating` DECIMAL(3,2) DEFAULT 0.00,
  `delivery_time` INT DEFAULT 30,
  `phone` VARCHAR(20),
  `address` TEXT,
  `image_url` VARCHAR(255),
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_owner_id (owner_id),
  INDEX idx_status (status),
  INDEX idx_area (area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 3. CATEGORIES TABLE
-- ============================================
CREATE TABLE `categories` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `restaurant_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  INDEX idx_restaurant_id (restaurant_id),
  UNIQUE KEY unique_restaurant_category (restaurant_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 4. MENU_ITEMS TABLE
-- ============================================
CREATE TABLE `menu_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `restaurant_id` INT NOT NULL,
  `category_id` INT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `image_url` VARCHAR(255),
  `is_available` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_restaurant_id (restaurant_id),
  INDEX idx_category_id (category_id),
  INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 5. CARTS TABLE
-- ============================================
CREATE TABLE `carts` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `restaurant_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_restaurant_id (restaurant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 6. CART_ITEMS TABLE
-- ============================================
CREATE TABLE `cart_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `cart_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
  INDEX idx_cart_id (cart_id),
  INDEX idx_menu_item_id (menu_item_id),
  UNIQUE KEY unique_cart_item (cart_id, menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 7. ORDERS TABLE
-- ============================================
CREATE TABLE `orders` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `delivery_address` TEXT,
  `delivery_phone` VARCHAR(20),
  `status` ENUM('pending', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_restaurant_id (restaurant_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 8. ORDER_ITEMS TABLE
-- ============================================
CREATE TABLE `order_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
  INDEX idx_order_id (order_id),
  INDEX idx_menu_item_id (menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 9. PAYMENTS TABLE
-- ============================================
CREATE TABLE `payments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `order_id` INT NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `method` ENUM('cash', 'card', 'mobile_money', 'wallet') DEFAULT 'cash',
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `transaction_id` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_order_id (order_id),
  INDEX idx_user_id (user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Admin User
INSERT INTO `users` (name, email, password, role, status) VALUES
('Admin User', 'admin@foodhub.com', '$2y$10$9VWzRfM8xYLAZ0vGC0X8KuQ1Z3f5jF6p8k4M2N3L5P6Q7R8S9', 'admin', 'active');

-- Restaurant Owner
INSERT INTO `users` (name, email, password, phone, address, role, status) VALUES
('John Burgers', 'john@burgers.com', '$2y$10$9VWzRfM8xYLAZ0vGC0X8KuQ1Z3f5jF6p8k4M2N3L5P6Q7R8S9', '+254712345678', 'Nairobi', 'owner', 'active'),
('Mary Kitchen', 'mary@kitchen.com', '$2y$10$9VWzRfM8xYLAZ0vGC0X8KuQ1Z3f5jF6p8k4M2N3L5P6Q7R8S9', '+254712345679', 'Nairobi', 'owner', 'active');

-- Restaurants
INSERT INTO `restaurants` (name, description, cuisine, area, owner_id, rating, delivery_time, phone, address, status) VALUES
('Burger King', 'Flame-grilled burgers', 'Fast Food', 'Westlands', 2, 4.5, 30, '+254700000001', 'Nairobi', 'active'),
('Karamu Kitchen', 'Traditional Kenyan meals', 'African', 'South B', 3, 4.7, 40, '+254700000002', 'Nairobi', 'active');

-- Categories
INSERT INTO `categories` (restaurant_id, name, description) VALUES
(1, 'Burgers', 'Delicious burgers'),
(1, 'Sides', 'Sides and extras'),
(2, 'Main Course', 'Main dishes'),
(2, 'Breakfast', 'Breakfast items');

-- Menu Items
INSERT INTO `menu_items` (restaurant_id, category_id, name, description, price, is_available) VALUES
(1, 1, 'Whopper Burger', 'Flame-grilled beef patty with fresh toppings', 450.00, TRUE),
(1, 1, 'Cheeseburger', 'Classic cheese burger', 350.00, TRUE),
(1, 2, 'French Fries', 'Crispy golden fries', 150.00, TRUE),
(1, 2, 'Soft Drink', 'Assorted beverages', 100.00, TRUE),
(2, 3, 'Ugali & Nyama Choma', 'Maize meal with grilled beef', 500.00, TRUE),
(2, 3, 'Sukuma Wiki & Ugali', 'Greens with maize meal', 300.00, TRUE),
(2, 4, 'Chips Mayai', 'French fries omelette', 250.00, TRUE),
(2, 4, 'Mandazi', 'Deep-fried pastry', 80.00, TRUE);

-- Sample Customer Users
INSERT INTO `users` (name, email, password, phone, address, role, status) VALUES
('Alice Customer', 'alice@example.com', '$2y$10$9VWzRfM8xYLAZ0vGC0X8KuQ1Z3f5jF6p8k4M2N3L5P6Q7R8S9', '+254712345680', 'Nairobi', 'customer', 'active'),
('Bob Client', 'bob@example.com', '$2y$10$9VWzRfM8xYLAZ0vGC0X8KuQ1Z3f5jF6p8k4M2N3L5P6Q7R8S9', '+254712345681', 'Nairobi', 'customer', 'active');
