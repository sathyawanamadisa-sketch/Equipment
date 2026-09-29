CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_number VARCHAR(50) NOT NULL UNIQUE,
    item_type VARCHAR(50) NOT NULL,
    brand_model VARCHAR(100) NOT NULL,
    status ENUM('Available', 'Issued') NOT NULL DEFAULT 'Available',
    section_division VARCHAR(100) NOT NULL,
    date_added DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
