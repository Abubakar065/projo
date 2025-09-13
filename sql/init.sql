CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pm','viewer') DEFAULT 'viewer'
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    contract_value_ngn DECIMAL(18,2),
    contract_value_usd DECIMAL(18,2),
    notice_award DATE,
    contract_signed DATE,
    commencement DATE,
    completion DATE,
    contractual_completion DATE
);

CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category ENUM('engineering','procurement','civil','installation','testing','overall','disbursement','planned') NOT NULL,
    percentage INT DEFAULT 0,
    month YEAR,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
