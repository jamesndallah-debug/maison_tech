<?php
include 'dp.php';

echo "<h2>Updating Database Schema for Bill Payments</h2>";

try {
    // Add kingamuzi and government to the provider ENUM in money_transactions table
    $sql1 = "ALTER TABLE money_transactions 
             MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') NOT NULL DEFAULT 'other'";
    
    if ($conn->query($sql1)) {
        echo "<p style='color: green;'>✓ Updated money_transactions provider ENUM</p>";
    } else {
        echo "<p style='color: orange;'>⚠ money_transactions provider ENUM already updated or error: " . $conn->error . "</p>";
    }

    // Add customer_name field for bill payments
    $sql2 = "ALTER TABLE money_transactions 
             ADD COLUMN customer_name VARCHAR(100) AFTER customer_msisdn";
    
    if ($conn->query($sql2)) {
        echo "<p style='color: green;'>✓ Added customer_name field to money_transactions</p>";
    } else {
        echo "<p style='color: orange;'>⚠ customer_name field already exists or error: " . $conn->error . "</p>";
    }

    // Add payment_service field for specific services
    $sql2b = "ALTER TABLE money_transactions 
              ADD COLUMN payment_service VARCHAR(50) AFTER customer_name";
    
    if ($conn->query($sql2b)) {
        echo "<p style='color: green;'>✓ Added payment_service field to money_transactions</p>";
    } else {
        echo "<p style='color: orange;'>⚠ payment_service field already exists or error: " . $conn->error . "</p>";
    }

    // Update money_float_opening table to include the new providers
    $sql3 = "ALTER TABLE money_float_opening 
             MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') PRIMARY KEY";
    
    if ($conn->query($sql3)) {
        echo "<p style='color: green;'>✓ Updated money_float_opening provider ENUM</p>";
    } else {
        echo "<p style='color: orange;'>⚠ money_float_opening provider ENUM already updated or error: " . $conn->error . "</p>";
    }

    // Insert opening balances for the new providers
    $sql4 = "INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES 
             ('kingamuzi', 0.00),
             ('government', 0.00)";
    
    if ($conn->query($sql4)) {
        echo "<p style='color: green;'>✓ Added opening balances for new providers</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Opening balances already exist or error: " . $conn->error . "</p>";
    }

    // Update money_daily_closing table to include the new providers
    $sql5 = "ALTER TABLE money_daily_closing 
             ADD COLUMN expected_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_mixx_by_yass,
             ADD COLUMN counted_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_kingamuzi,
             ADD COLUMN variance_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_kingamuzi,
             ADD COLUMN expected_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_kingamuzi,
             ADD COLUMN counted_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_government,
             ADD COLUMN variance_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_government";
    
    if ($conn->query($sql5)) {
        echo "<p style='color: green;'>✓ Updated money_daily_closing table with new provider columns</p>";
    } else {
        echo "<p style='color: orange;'>⚠ money_daily_closing columns already exist or error: " . $conn->error . "</p>";
    }

    echo "<h3 style='color: green;'>Database schema update completed!</h3>";
    echo "<p><a href='payments.php'>Go to Bill Payments page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
