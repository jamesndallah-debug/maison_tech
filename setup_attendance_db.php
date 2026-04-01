<?php
include 'dp.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT
    )",
    "INSERT INTO settings (setting_key, setting_value) VALUES ('company_ip', '') ON DUPLICATE KEY UPDATE setting_key=setting_key",
    "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        sign_in_time DATETIME NOT NULL,
        sign_out_time DATETIME DEFAULT NULL,
        date DATE NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Query executed successfully.\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}

echo "Database setup for attendance complete.";
?>