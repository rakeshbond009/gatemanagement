USE gate_management;

-- Add user_units table for many-to-many relationship
CREATE TABLE IF NOT EXISTS user_units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    unit_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    UNIQUE KEY unique_user_unit (user_id, unit_id)
);

-- Drop and recreate visitors table
DROP TABLE IF EXISTS visitors;
CREATE TABLE visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    photo VARCHAR(255) NULL,
    host_type ENUM('resident', 'staff', 'other') NOT NULL DEFAULT 'resident',
    host_id INT NULL,  -- For resident/staff references
    host_name VARCHAR(100) NULL, -- For non-resident hosts
    host_department VARCHAR(100) NULL, -- For staff/other
    check_in_time DATETIME NOT NULL,
    check_out_time DATETIME NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Update notifications table if data column doesn't exist
ALTER TABLE notifications 
    MODIFY COLUMN type ENUM('visitor', 'emergency', 'announcement', 'visitor_approval', 'visitor_checkout') NOT NULL;

-- Add data column if it doesn't exist
ALTER TABLE notifications 
    ADD COLUMN IF NOT EXISTS data TEXT NULL AFTER message;
