-- ============================================
-- BUCOSA e-Reg Database Structure
-- Bishop Stuart University Computing Students Association
-- Advanced & Professional SQL Schema (Single Users Table)
-- ============================================

DROP DATABASE IF EXISTS bucosa_ereg;
CREATE DATABASE bucosa_ereg;
USE bucosa_ereg;

-- ============================================
-- USERS TABLE (Combined Students, President, Admins)
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'president', 'admin', 'super_admin') NOT NULL DEFAULT 'student',
    
    -- Student specific fields (Nullable for others)
    reg_number VARCHAR(100) UNIQUE DEFAULT NULL,
    course VARCHAR(150) DEFAULT NULL,
    year_of_study VARCHAR(50) DEFAULT NULL,
    
    -- Common Profile Fields
    gender ENUM('Male','Female') DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    
    -- Security & Logs
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- SESSIONS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    attendance_started_at DATETIME DEFAULT NULL,
    attendance_expires_at DATETIME DEFAULT NULL,
    attendance_close_time DATETIME DEFAULT NULL,
    venue VARCHAR(255) NOT NULL,
    description TEXT,
    qr_token VARCHAR(255) NOT NULL UNIQUE,
    session_banner VARCHAR(255) DEFAULT NULL,
    max_participants INT DEFAULT 100,
    status ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- SESSION TYPES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS session_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO session_types (name, description, is_active) VALUES
('Web Development', 'Frontend and backend development sessions', 1),
('Cybersecurity', 'Security awareness, defense, and ethical hacking', 1),
('Networking', 'Computer networking and infrastructure', 1),
('AI & Machine Learning', 'Artificial intelligence and machine learning', 1),
('Programming Bootcamp', 'Intensive programming training sessions', 1),
('Workshop', 'General workshop or special event', 1);

-- ============================================
-- ENROLLMENTS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    enrollment_status ENUM('pending','approved','rejected') DEFAULT 'approved',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    UNIQUE(student_id, session_id)
);

-- ============================================
-- ATTENDANCE TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    attendance_status ENUM('present','late','absent') DEFAULT 'present',
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    device_ip VARCHAR(100) DEFAULT NULL,

    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    UNIQUE(student_id, session_id)
);

-- ============================================
-- LEARNING MATERIALS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(100) DEFAULT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- ANNOUNCEMENTS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    announcement_type ENUM('general','event','urgent') DEFAULT 'general',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student','president','admin') NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- SKILLS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
    awarded_on DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- CERTIFICATES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    certificate_code VARCHAR(255) UNIQUE NOT NULL,
    issued_date DATE NOT NULL,
    certificate_file VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- ============================================
-- SYSTEM LOGS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student','president','admin','super_admin') NOT NULL,
    activity TEXT NOT NULL,
    ip_address VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- CONTACT MESSAGES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read','replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_reg ON users(reg_number);
CREATE INDEX idx_session_date ON sessions(session_date);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_attendance_session ON attendance(session_id);
CREATE INDEX idx_notification_user ON notifications(user_id);

-- ============================================
-- DEFAULT PRESIDENT ACCOUNT
-- Password: password
-- ============================================

INSERT INTO users (full_name, email, phone, password, role)
VALUES (
    'BUCOSA President',
    'president@bucosa.com',
    '+256700000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'president'
);

-- ============================================
-- DEFAULT ADMIN ACCOUNT
-- Password: password
-- ============================================

INSERT INTO users (full_name, email, phone, password, role)
VALUES (
    'System Administrator',
    'admin@bucosa.com',
    '+256700000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- ============================================
-- END OF BUCOSA e-Reg DATABASE
-- ============================================