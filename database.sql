CREATE DATABASE IF NOT EXISTS ekhaya_reservations CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ekhaya_reservations;

-- 1. Users Table (Guests, Hosts, Admins)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('guest', 'host', 'admin') NOT NULL DEFAULT 'guest',
    is_verified_safe TINYINT(1) NOT NULL DEFAULT 0, -- 1 = Verified Safe by Admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Properties Table (Hotels, Houses, Lodges)
CREATE TABLE properties (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    property_type ENUM('Hotel', 'House', 'Lodge') NOT NULL,
    city VARCHAR(80) NOT NULL,
    address VARCHAR(200) NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    description TEXT NOT NULL,
    checkin_instructions TEXT NOT NULL,
    checkout_instructions TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Bookings Table (Guest Reservations)
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    guest_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    guests_count INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Support Tickets Table (Host-to-Admin Support)
CREATE TABLE support_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT NULL,
    status ENUM('Open', 'Answered') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Default Admin Account (Email: admin@ekhaya.co.zw | Password: password123)
INSERT INTO users (full_name, email, phone, password_hash, role, is_verified_safe)
VALUES (
    'Ekhaya Admin',
    'admin@ekhaya.co.zw',
    '+263 77 000 0000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    1
);