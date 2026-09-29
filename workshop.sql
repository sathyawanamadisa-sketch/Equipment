-- Add "Workshop" as a third possible status for equipment (alongside Available / Issued)
ALTER TABLE equipment MODIFY status ENUM('Available', 'Issued', 'Workshop') NOT NULL DEFAULT 'Available';

-- Workshop (G7) admission log
CREATE TABLE IF NOT EXISTS workshop (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    admit_date DATE NOT NULL,
    workshop_job_number VARCHAR(50) NOT NULL,
    return_date DATE NULL,
    status ENUM('In Workshop', 'Repaired') NOT NULL DEFAULT 'In Workshop',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
