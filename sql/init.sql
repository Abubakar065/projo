-- Project Tracking and Reporting Application Database Schema
-- Version: 1.0
-- Created: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS project_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE project_tracker;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'pm', 'viewer') DEFAULT 'viewer',
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(200) NOT NULL,
    contract_description TEXT,
    scope_of_work TEXT,
    contract_value_ngn DECIMAL(15,2),
    contract_value_usd DECIMAL(15,2),
    notice_of_award DATE,
    contract_signed DATE,
    commencement_date DATE,
    proposed_completion DATE,
    contractual_completion DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Project progress table
CREATE TABLE project_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    engineering_design DECIMAL(5,2) DEFAULT 0,
    procurement DECIMAL(5,2) DEFAULT 0,
    civil DECIMAL(5,2) DEFAULT 0,
    installation DECIMAL(5,2) DEFAULT 0,
    testing_commissioning DECIMAL(5,2) DEFAULT 0,
    disbursement_progress DECIMAL(5,2) DEFAULT 0,
    planned_progress DECIMAL(5,2) DEFAULT 0,
    actual_progress DECIMAL(5,2) DEFAULT 0,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Monthly summary table
CREATE TABLE monthly_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    activity_description TEXT NOT NULL,
    duration_weeks INT NOT NULL,
    serial_number INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Planned activities table
CREATE TABLE planned_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    activity_description TEXT NOT NULL,
    duration_weeks INT NOT NULL,
    responsible_party VARCHAR(100),
    remarks TEXT,
    serial_number INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Project images table
CREATE TABLE project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    image_caption TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Insert default admin user
INSERT INTO users (username, password, email, role, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@company.com', 'admin', 'System Administrator');

-- Insert sample users
INSERT INTO users (username, password, email, role, full_name) VALUES 
('john_pm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john@company.com', 'pm', 'John Smith'),
('mary_viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mary@company.com', 'viewer', 'Mary Johnson');

-- Insert sample projects
INSERT INTO projects (project_name, contract_description, scope_of_work, contract_value_ngn, contract_value_usd, notice_of_award, contract_signed, commencement_date, proposed_completion, contractual_completion, created_by) VALUES 
('Power Grid Upgrade Phase 1', 'Upgrade of transmission lines and substations in Lagos State', 'Installation of new 330kV transmission lines, upgrade of 3 substations, installation of protection systems', 15000000000.00, 37500000.00, '2024-01-15', '2024-02-01', '2024-02-15', '2024-12-31', '2025-01-31', 1),
('Rural Electrification Project', 'Extension of electricity to 50 rural communities', 'Construction of 11kV distribution lines, installation of transformers, connection of households', 8500000000.00, 21250000.00, '2024-03-01', '2024-03-20', '2024-04-01', '2024-11-30', '2024-12-31', 1),
('Smart Grid Implementation', 'Implementation of smart metering and grid automation', 'Installation of smart meters, SCADA systems, and automated switching equipment', 12000000000.00, 30000000.00, '2024-02-10', '2024-02-28', '2024-03-15', '2025-02-28', '2025-03-31', 1);

-- Insert sample progress data
INSERT INTO project_progress (project_id, engineering_design, procurement, civil, installation, testing_commissioning, disbursement_progress, planned_progress, actual_progress, updated_by) VALUES 
(1, 85.0, 70.0, 60.0, 45.0, 20.0, 65.0, 70.0, 56.0, 1),
(2, 95.0, 80.0, 75.0, 65.0, 30.0, 70.0, 75.0, 69.0, 1),
(3, 60.0, 40.0, 30.0, 15.0, 5.0, 35.0, 45.0, 30.0, 1);

-- Insert sample monthly activities
INSERT INTO monthly_summary (project_id, month_year, activity_description, duration_weeks, serial_number) VALUES 
(1, '2024-12', 'Installation of protection relays at Ikeja substation', 2, 1),
(1, '2024-12', 'Testing of 330kV transmission line section A-B', 3, 2),
(1, '2024-12', 'Commissioning of control systems', 1, 3),
(2, '2024-12', 'Connection of households in Ogun communities', 4, 1),
(2, '2024-12', 'Installation of distribution transformers', 2, 2),
(3, '2024-12', 'Smart meter deployment in pilot area', 3, 1),
(3, '2024-12', 'SCADA system configuration', 2, 2);

-- Insert sample planned activities
INSERT INTO planned_activities (project_id, month_year, activity_description, duration_weeks, responsible_party, remarks, serial_number) VALUES 
(1, '2025-01', 'Final testing and commissioning of all systems', 4, 'Technical Team', 'Critical path activity', 1),
(1, '2025-01', 'Documentation and handover preparation', 2, 'Project Manager', 'Parallel with testing', 2),
(2, '2025-01', 'Final connections and meter installations', 3, 'Installation Team', 'Weather dependent', 1),
(2, '2025-01', 'Community training and handover', 1, 'Community Relations', 'After connections complete', 2),
(3, '2025-01', 'System integration testing', 3, 'IT Team', 'Requires vendor support', 1),
(3, '2025-01', 'User training and documentation', 2, 'Training Team', 'Parallel activity', 2);
