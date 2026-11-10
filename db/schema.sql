-- Nicole's Flowers & Greens - Database Schema
-- Run this in phpMyAdmin or the MySQL command line.

CREATE DATABASE IF NOT EXISTS nicole_flower CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nicole_flower;

-- Users table (customers & owner)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    address TEXT,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','owner') DEFAULT 'customer',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bouquets / Products table
CREATE TABLE IF NOT EXISTS bouquets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-bouquet.jpg',
    category VARCHAR(50) DEFAULT 'bouquet',
    stock INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    customization DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    downpayment DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    fulfillment_status ENUM('pending','arranging','ready','complete') DEFAULT 'pending',
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'cash',
    reference_number VARCHAR(100),
    pickup_date DATE,
    pickup_time VARCHAR(50),
    recipient_name VARCHAR(100),
    gift_note TEXT,
    florist_note TEXT,
    shipping_address TEXT,
    phone VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    bouquet_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages / Inbox table. Each row is one message in a customer thread.
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    order_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    sender ENUM('customer','owner') DEFAULT 'customer',
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table (shop settings)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default owner account (password: admin123)
INSERT INTO users (full_name, email, password, role, status) VALUES
('Nicole', 'owner@nicoleflower.com', '$2y$10$K4jAd93IIYIqcKo/DqhRled0S1skL/Pw/Vcay/AWHV8NIGlwdK9iW', 'owner', 'active');

-- Sample bouquets. Images live in assets/images/.
INSERT INTO bouquets (name, description, price, image, category, stock, featured, is_new, status) VALUES
('Lilac Rose Ribbon', 'Lavender rose, eucalyptus & pink satin ribbon', 1180.00, 'lilac-rose-ribbon.jpg', 'bouquet', 10, 1, 0, 'active'),
('Blue Rose Navy', 'Blue-tinted rose, solidago & navy satin ribbon', 1350.00, 'blue-rose-navy.jpg', 'bouquet', 10, 0, 0, 'active'),
('Sunflower Noir', 'Sunflower, baby''s breath & dried filler in black wrap', 550.00, 'sunflower-noir.jpg', 'bouquet', 10, 0, 1, 'active'),
('Sunset Rose Noir', 'Orange rose, red rose & seasonal blooms in black & gold wrap', 2500.00, 'sunset-rose-noir.jpg', 'bouquet', 8, 0, 0, 'active'),
('Red Rose & Statice', 'Red rose, dried statice & red satin ribbon', 600.00, 'red-rose-statice.jpg', 'bouquet', 10, 1, 0, 'active'),
('Lily Sunflower Rustic', 'Lily, sunflower, pink carnation & burlap wrap', 2000.00, 'lily-sunflower-rustic.jpg', 'bouquet', 8, 0, 0, 'active'),
('Coral Carnation Peach', 'Coral carnation, salal leaves & cream-peach wrap', 950.00, 'coral-carnation-peach.jpg', 'bouquet', 10, 0, 1, 'active'),
('White Tulips', 'Dahlia, rose, snapdragon & eucalyptus', 300.00, 'white-tulips.jpg', 'bouquet', 12, 0, 0, 'active'),
('Orange Rose Peach Wrap', 'Orange rose, leather leaf & cream-peach wrap', 350.00, 'orange-rose-peach-wrap.jpg', 'bouquet', 12, 1, 0, 'active'),
('Red Rose Burlap', 'Red rose, dried filler & burlap mesh wrap', 1500.00, 'red-rose-burlap.jpg', 'bouquet', 10, 0, 0, 'active'),
('Four Sunflower Noir', 'Sunflower, dried filler & black wrap with gray ribbon', 1600.00, 'four-sunflower-noir.jpg', 'bouquet', 8, 0, 1, 'active'),
('Pink Rose Red Bow', 'Pink rose, white wrap & red satin bow', 450.00, 'pink-rose-red-bow.jpg', 'bouquet', 12, 0, 0, 'active'),
('The Clara', 'Garden rose, ranunculus, sweet pea & jasmine', 88.00, 'the-clara.jpg', 'signature', 10, 1, 0, 'active'),
('Meadow Muse', 'Soft blush, creamy whites & trailing greens', 76.00, 'meadow-muse.jpg', 'signature', 10, 1, 0, 'active'),
('The Sylvie', 'Warm autumn tones with textured foliage', 94.00, 'the-sylvie.jpg', 'signature', 10, 1, 0, 'active'),
('Apricot Hour', 'Apricot roses & seasonal blooms', 110.00, 'apricot-hour.jpg', 'signature', 10, 1, 0, 'active');

-- Sample customer accounts (password: flower123)
INSERT INTO users (full_name, email, phone, address, password, role, status) VALUES
('Ana Reyes', 'ana@example.com', '0917 000 0148', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Miguel Santos', 'miguel@example.com', '0917 000 0149', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Bea Navarro', 'bea@example.com', '0917 000 0150', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Paolo Cruz', 'paolo@example.com', '0917 000 0151', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Isabel Lim', 'isabel@example.com', '0917 000 0152', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Luis Garcia', 'luis@example.com', '0917 000 0153', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Camille Flores', 'camille@example.com', '0917 000 0154', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active'),
('Mika Dela Cruz', 'mika@example.com', '0917 000 0155', 'Nicole''s Flowers & Greens shop', '$2y$10$j80GYvHNsqEPzsjeYr4qk.EZT8SU5/h8LlmK6bdeFlm72MWIYNb2.', 'customer', 'active');

-- Sample orders used by the admin screens.
INSERT INTO orders (user_id, order_number, subtotal, customization, total, downpayment, payment_status, fulfillment_status, status, payment_method, reference_number, pickup_date, pickup_time, recipient_name, gift_note, florist_note, shipping_address, phone, created_at) VALUES
((SELECT id FROM users WHERE email='ana@example.com'), '1048', 88.00, 20.00, 108.00, 54.00, 'paid', 'arranging', 'processing', 'GCash', 'GC-1048', '2026-10-13', '10 am – 12 pm', 'Clara Santos', 'A little beauty for your day. With love, always.', 'Soft blush tones, please. Keep the trailing greens and avoid lilies.', 'Nicole''s Flowers & Greens shop', '0917 000 0148', '2026-10-12 16:32:00'),
((SELECT id FROM users WHERE email='miguel@example.com'), '1047', 76.00, 0.00, 76.00, 38.00, 'paid', 'complete', 'completed', 'GCash', 'GC-1047', '2026-10-13', '10 am – 12 pm', 'Miguel Santos', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0149', '2026-10-12 15:18:00'),
((SELECT id FROM users WHERE email='bea@example.com'), '1046', 94.00, 0.00, 94.00, 47.00, 'paid', 'ready', 'processing', 'Bank transfer', 'BT-1046', '2026-10-13', '1 pm – 3 pm', 'Bea Navarro', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0150', '2026-10-12 14:05:00'),
((SELECT id FROM users WHERE email='paolo@example.com'), '1045', 110.00, 0.00, 110.00, 55.00, 'pending', 'pending', 'pending', 'Pay at shop', NULL, '2026-10-13', '1 pm – 3 pm', 'Paolo Cruz', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0151', '2026-10-12 11:40:00'),
((SELECT id FROM users WHERE email='isabel@example.com'), '1044', 176.00, 0.00, 176.00, 88.00, 'paid', 'arranging', 'processing', 'GCash', 'GC-1044', '2026-10-14', '10 am – 12 pm', 'Isabel Lim', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0152', '2026-10-11 17:12:00'),
((SELECT id FROM users WHERE email='luis@example.com'), '1043', 76.00, 0.00, 76.00, 38.00, 'pending', 'pending', 'pending', 'Pay at shop', NULL, '2026-10-14', '1 pm – 3 pm', 'Luis Garcia', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0153', '2026-10-11 16:06:00'),
((SELECT id FROM users WHERE email='mika@example.com'), '1042', 94.00, 0.00, 94.00, 47.00, 'paid', 'complete', 'completed', 'GCash', 'GC-1042', '2026-10-12', '3 pm – 5 pm', 'Mika Dela Cruz', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0155', '2026-10-10 11:22:00'),
((SELECT id FROM users WHERE email='camille@example.com'), '1041', 110.00, 0.00, 110.00, 55.00, 'paid', 'complete', 'completed', 'Bank transfer', 'BT-1041', '2026-10-12', '1 pm – 3 pm', 'Camille Flores', NULL, NULL, 'Nicole''s Flowers & Greens shop', '0917 000 0154', '2026-10-10 14:35:00');

INSERT INTO order_items (order_id, bouquet_id, quantity, price) VALUES
((SELECT id FROM orders WHERE order_number='1048'), (SELECT id FROM bouquets WHERE name='The Clara'), 1, 88.00),
((SELECT id FROM orders WHERE order_number='1047'), (SELECT id FROM bouquets WHERE name='Meadow Muse'), 1, 76.00),
((SELECT id FROM orders WHERE order_number='1046'), (SELECT id FROM bouquets WHERE name='The Sylvie'), 1, 94.00),
((SELECT id FROM orders WHERE order_number='1045'), (SELECT id FROM bouquets WHERE name='Apricot Hour'), 1, 110.00),
((SELECT id FROM orders WHERE order_number='1044'), (SELECT id FROM bouquets WHERE name='The Clara'), 2, 88.00),
((SELECT id FROM orders WHERE order_number='1043'), (SELECT id FROM bouquets WHERE name='Meadow Muse'), 1, 76.00),
((SELECT id FROM orders WHERE order_number='1042'), (SELECT id FROM bouquets WHERE name='The Sylvie'), 1, 94.00),
((SELECT id FROM orders WHERE order_number='1041'), (SELECT id FROM bouquets WHERE name='Apricot Hour'), 1, 110.00);

-- Sample conversations for the inbox.
INSERT INTO messages (user_id, order_id, name, email, sender, message, is_read, created_at) VALUES
((SELECT id FROM users WHERE email='ana@example.com'), (SELECT id FROM orders WHERE order_number='1048'), 'Ana Reyes', 'ana@example.com', 'customer', 'Hi! Can you add a few peonies to The Clara? How much would that be?', 1, NOW() - INTERVAL 2 MINUTE),
((SELECT id FROM users WHERE email='ana@example.com'), (SELECT id FROM orders WHERE order_number='1048'), 'Ana Reyes', 'ana@example.com', 'owner', 'Of course! Let me check what''s in season today.', 1, NOW() - INTERVAL 1 MINUTE),
((SELECT id FROM users WHERE email='miguel@example.com'), (SELECT id FROM orders WHERE order_number='1047'), 'Miguel Santos', 'miguel@example.com', 'customer', 'Is a bigger bouquet possible?', 0, NOW() - INTERVAL 1 HOUR),
((SELECT id FROM users WHERE email='isabel@example.com'), (SELECT id FROM orders WHERE order_number='1044'), 'Isabel Lim', 'isabel@example.com', 'customer', 'Can I swap the ribbon color?', 0, NOW() - INTERVAL 3 HOUR),
((SELECT id FROM users WHERE email='bea@example.com'), (SELECT id FROM orders WHERE order_number='1046'), 'Bea Navarro', 'bea@example.com', 'customer', 'Thanks, the quoted price works.', 1, NOW() - INTERVAL 1 DAY);

-- Default shop settings
INSERT INTO settings (`key`, `value`) VALUES
('shop_name', 'Nicole''s Flowers & Greens'),
('shop_phone', '0917 123 4567'),
('shop_email', 'hello@nicolesflowersgreens.com'),
('shop_address', 'Nicole''s Flowers & Greens shop'),
('shop_open', '10 am – 6 pm'),
('downpayment_percent', '50'),
('payment_gcash', '0912-345-6789'),
('payment_maya', '0912-345-6789'),
('payment_bank', 'Add account'),
('payment_cash', 'Pay at the shop');
