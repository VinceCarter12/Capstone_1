-- ============================================
-- InternTrack Website Database Schema
-- ============================================
-- This is a SEPARATE database for the PHP website portal.
-- It is NOT the Laravel API database.
-- 
-- The website uses this for:
--   - Admin/professor login (local auth)
--   - Displaying student and company data (read-only cache)
--   - Managing company assignments
--
-- IMPORTANT: The Laravel API is the single source of truth
-- for student accounts and authentication. This database
-- only stores display/cache data.
-- ============================================

-- Create database (uncomment if needed)
-- CREATE DATABASE interntrack_website;
-- USE interntrack_website;

-- ============================================
-- Companies Table
-- ============================================
CREATE TABLE IF NOT EXISTS companies (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(150) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Students Table (display cache only)
-- ============================================
-- NOTE: This is a read-only cache for display purposes.
-- Student accounts are managed by the Laravel API.
-- Do NOT use this table for authentication.
CREATE TABLE IF NOT EXISTS students (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(50),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20),
    course VARCHAR(50),
    year INT(11),
    section VARCHAR(10),
    company_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Admin Table (website portal login)
-- ============================================
-- Used for professor/admin login on the PHP website.
-- These are separate from Laravel API users.
CREATE TABLE IF NOT EXISTS admin (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    adminName VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Import Audit Log (optional, for tracking uploads)
-- ============================================
-- Tracks Excel uploads made through the website.
-- The actual import data is stored in Laravel API.
CREATE TABLE IF NOT EXISTS import_logs (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    admin_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    total_rows INT(11) DEFAULT 0,
    api_import_id INT(11),  -- Reference to Laravel's student_imports.id
    status ENUM('uploaded', 'sent_to_api', 'confirmed', 'failed') DEFAULT 'uploaded',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (optional - uncomment to use)
-- ============================================

-- Insert sample admin (password: 'password123' - CHANGE IN PRODUCTION!)
-- Password should be hashed with PHP's password_hash() function
-- INSERT INTO admin (adminName, password, email) VALUES 
-- ('Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@pup.edu.ph');

-- Insert sample companies
-- INSERT INTO companies (company_name, address, contact_person, contact_number) VALUES
-- ('TechCorp Inc.', '123 Main St, Manila', 'John Doe', '09171234567'),
-- ('DevStudio PH', '456 Rizal Ave, Quezon City', 'Jane Smith', '09181234567');
