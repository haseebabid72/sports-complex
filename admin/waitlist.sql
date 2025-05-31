CREATE TABLE IF NOT EXISTS waitlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(5) NOT NULL,
    request_time DATETIME NOT NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id),
    FOREIGN KEY (user_id) REFERENCES players(player_id)
);
