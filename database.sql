-- ===============================================
-- CREATE DATABASE
-- ===============================================
CREATE DATABASE IF NOT EXISTS trashbin_management;
USE trashbin_management;

-- ===============================================
-- ADMINS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    employee_id VARCHAR(50) UNIQUE,
    profile_picture VARCHAR(255),
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- JANITORS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS janitors (
    janitor_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    employee_id VARCHAR(50) UNIQUE,
    profile_picture VARCHAR(255),
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- BINS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS bins (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_code VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('General','Recyclable','Organic') NOT NULL DEFAULT 'General',
    capacity INT NOT NULL DEFAULT 0,
    status ENUM('empty','needs_attention','full','in_progress','out_of_service') NOT NULL DEFAULT 'empty',
    assigned_to INT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    installation_date DATE,
    notes TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES janitors(janitor_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- COLLECTIONS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS collections (
    collection_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    janitor_id INT NOT NULL,
    action_type ENUM('emptied','cleaning','inspection','maintenance','repair') NOT NULL,
    status ENUM('completed','in_progress','pending','cancelled') NOT NULL DEFAULT 'completed',
    notes TEXT,
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- TASKS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    janitor_id INT NOT NULL,
    task_type ENUM('empty','inspection','maintenance','repair') NOT NULL,
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- NOTIFICATIONS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    janitor_id INT NULL,
    bin_id INT NULL,
    notification_type ENUM('critical','warning','info','success') NOT NULL DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- REPORTS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('collections','performance','status','revenue','custom') NOT NULL,
    generated_by INT NOT NULL,
    date_from DATE,
    date_to DATE,
    report_data JSON,
    format ENUM('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
    status ENUM('generating','completed','failed') NOT NULL DEFAULT 'generating',
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- BIN STATUS HISTORY TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS bin_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NULL,
    notes TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- ACTIVITY LOGS TABLE
-- ===============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    janitor_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================
-- INSERT DEFAULT ADMIN
-- Password = "password" (bcrypt)
-- ===============================================
INSERT INTO admins (
    first_name, last_name, email, phone, password, status, employee_id
) VALUES (
    'Admin','User','admin@gmail.com','+1 (555) 000-0000',
    '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62',
    'active','ADM-001'
);

-- ===============================================
-- SAMPLE JANITORS
-- ===============================================
INSERT INTO janitors (
    first_name, last_name, email, phone, password, status, employee_id
) VALUES
('John','Doe','john@gmail.com','+1 (555) 123-4567',
 '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62','active','JAN-001'),
('Jane','Smith','jane@gmail.com','+1 (555) 234-5678',
 '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62','active','JAN-002'),
('Bob','Johnson','bob@gmail.com','+1 (555) 345-6789',
 '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62','active','JAN-003');

-- ===============================================
-- SAMPLE BINS
-- ===============================================
INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to) VALUES
('BIN-001','CICS Building - 5th Floor','General',95,'full',1),
('BIN-002','HEB Building - 1st and 2nd Floor','General',10,'empty',2),
('BIN-003','HEB Building - 3rd to 5th Floor','Organic',80,'needs_attention',3),
('BIN-004','GZB Building - 1st to 3rd Floor','General',45,'needs_attention',1),
('BIN-005','OB Building - 1st and 2nd Floor','Recyclable',60,'needs_attention',2);

-- ===============================================
-- SAMPLE COLLECTIONS
-- ===============================================
INSERT INTO collections (bin_id, janitor_id, action_type, status, notes) VALUES
(1,1,'emptied','completed','Regular emptying'),
(2,2,'cleaning','completed','Cleaned and sanitized'),
(3,3,'inspection','completed','Routine check');

-- ===============================================
-- SAMPLE TASKS
-- ===============================================
INSERT INTO tasks (bin_id, janitor_id, task_type, priority, status, notes) VALUES
(1,1,'empty','critical','pending','Full bin, urgent'),
(3,3,'inspection','medium','pending','Routine inspection'),
(5,2,'maintenance','high','pending','Needs repair');

-- ===============================================
-- SAMPLE NOTIFICATIONS
-- ===============================================
INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message)
VALUES
(1,NULL,1,'critical','Bin Full','BIN-001 is full!'),
(NULL,1,3,'warning','Inspection Needed','BIN-003 needs inspection');

-- ===============================================
-- SAMPLE ACTIVITY LOGS
-- ===============================================
INSERT INTO activity_logs (admin_id, action, entity_type, entity_id, description)
VALUES
(1,'login','admin',1,'Admin logged in');

-- ===============================================
-- VIEWS
-- ===============================================

-- Active Janitors + Assigned Bin Count
CREATE OR REPLACE VIEW v_active_janitors AS
SELECT 
    j.janitor_id,
    j.first_name,
    j.last_name,
    j.email,
    j.phone,
    j.status,
    COUNT(b.bin_id) AS assigned_bins
FROM janitors j
LEFT JOIN bins b ON b.assigned_to = j.janitor_id
WHERE j.status='active'
GROUP BY j.janitor_id;

-- Bin Statistics
CREATE OR REPLACE VIEW v_bin_statistics AS
SELECT 
    status,
    type,
    COUNT(*) AS total,
    AVG(capacity) AS average_capacity
FROM bins
GROUP BY status, type;

-- Recent Collections
CREATE OR REPLACE VIEW v_recent_collections AS
SELECT 
    c.collection_id,
    b.bin_code,
    b.location,
    CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
    c.action_type,
    c.status,
    c.collected_at
FROM collections c
JOIN bins b ON c.bin_id = b.bin_id
JOIN janitors j ON c.janitor_id = j.janitor_id
ORDER BY c.collected_at DESC
LIMIT 50;
