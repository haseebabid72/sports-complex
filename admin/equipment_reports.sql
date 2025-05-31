CREATE TABLE IF NOT EXISTS equipment_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    user_id INT,
    report TEXT NOT NULL,
    report_time DATETIME NOT NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (user_id) REFERENCES players(player_id)
);
