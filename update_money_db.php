<?php
include 'dp.php';

// Check if settings table exists, if not create it (precautionary)
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
)");

// Update providers in existing tables
// We'll add new columns to money_daily_closing and update money_float_opening

$alter_queries = [
    // 1. Rename 'tigopesa' to 'mixx_by_yass' in float opening if it exists, or just ensure we have the entry
    "INSERT INTO money_float_opening (provider, opening_float) VALUES ('mixx_by_yass', 0) ON DUPLICATE KEY UPDATE provider=provider",
    
    // 2. Add entries for new providers in money_float_opening
    "INSERT INTO money_float_opening (provider, opening_float) VALUES ('azam_pesa', 0) ON DUPLICATE KEY UPDATE provider=provider",
    "INSERT INTO money_float_opening (provider, opening_float) VALUES ('bank_agency', 0) ON DUPLICATE KEY UPDATE provider=provider",
    "INSERT INTO money_float_opening (provider, opening_float) VALUES ('kingamuzi', 0) ON DUPLICATE KEY UPDATE provider=provider",
    "INSERT INTO money_float_opening (provider, opening_float) VALUES ('government', 0) ON DUPLICATE KEY UPDATE provider=provider",

    // 3. Add columns to money_daily_closing for the new providers
    "ALTER TABLE money_daily_closing ADD COLUMN expected_float_mixx_by_yass DECIMAL(15,2) DEFAULT 0 AFTER expected_float_mixx_by_yass",
    "ALTER TABLE money_daily_closing ADD COLUMN counted_float_mixx_by_yass DECIMAL(15,2) DEFAULT 0 AFTER counted_float_mixx_by_yass",
    "ALTER TABLE money_daily_closing ADD COLUMN variance_float_mixx_by_yass DECIMAL(15,2) DEFAULT 0 AFTER variance_float_mixx_by_yass",
    
    "ALTER TABLE money_daily_closing ADD COLUMN expected_float_azam_pesa DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN counted_float_azam_pesa DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN variance_float_azam_pesa DECIMAL(15,2) DEFAULT 0",
    
    "ALTER TABLE money_daily_closing ADD COLUMN expected_float_bank_agency DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN counted_float_bank_agency DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN variance_float_bank_agency DECIMAL(15,2) DEFAULT 0",
    
    "ALTER TABLE money_daily_closing ADD COLUMN expected_float_kingamuzi DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN counted_float_kingamuzi DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN variance_float_kingamuzi DECIMAL(15,2) DEFAULT 0",
    
    "ALTER TABLE money_daily_closing ADD COLUMN expected_float_government DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN counted_float_government DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE money_daily_closing ADD COLUMN variance_float_government DECIMAL(15,2) DEFAULT 0",
    
    // 4. Update existing transactions from 'tigopesa' to 'mixx_by_yass'
    "UPDATE money_transactions SET provider = 'mixx_by_yass' WHERE provider = 'tigopesa'",
    "UPDATE money_float_opening SET provider = 'mixx_by_yass' WHERE provider = 'tigopesa'"
];

foreach ($alter_queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully executed: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . " (Query: " . substr($sql, 0, 50) . "...)\n";
    }
}

echo "\nDatabase update complete.";
?>
