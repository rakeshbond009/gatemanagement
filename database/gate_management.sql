-- Create database
CREATE DATABASE IF NOT EXISTS gate_management;

-- Users table (Residents and Staff)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('resident', 'security', 'staff', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'rejected') DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flats/Units table
CREATE TABLE IF NOT EXISTS units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    unit_number VARCHAR(20) NOT NULL,
    block_number VARCHAR(20) NOT NULL,
    status ENUM('occupied', 'vacant') DEFAULT 'vacant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User-Units relationship table (for residents)
CREATE TABLE IF NOT EXISTS user_units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    unit_id INT NOT NULL,
    is_owner BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    UNIQUE KEY unique_user_unit (user_id, unit_id)
);

-- Visitors table
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    photo_url VARCHAR(255),
    unit_id INT NOT NULL,
    host_id INT NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exit_time TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected', 'inside', 'left') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (host_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Staff Attendance table
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT,
    check_in TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out TIMESTAMP NULL,
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('visitor', 'emergency', 'announcement') NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Emergency Contacts table
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
