<?php
include 'dp.php';

echo "<h2>Fixing payment_service column</h2>";

// Check if payment_service column exists
$check_column = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'payment_service'");
if ($check_column->num_rows == 0) {
    echo "<p style='color: orange;'>⚠ payment_service column does not exist. Adding it...</p>";
    
    // Add the column
    $sql = "ALTER TABLE money_transactions ADD COLUMN payment_service VARCHAR(50) AFTER customer_name";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Added payment_service column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding payment_service column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ payment_service column already exists</p>";
}

// Check if customer_name column exists
$check_customer = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'customer_name'");
if ($check_customer->num_rows == 0) {
    echo "<p style='color: orange;'>⚠ customer_name column does not exist. Adding it...</p>";
    
    // Add the column
    $sql = "ALTER TABLE money_transactions ADD COLUMN customer_name VARCHAR(100) AFTER customer_msisdn";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Added customer_name column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding customer_name column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ customer_name column already exists</p>";
}

echo "<p><a href='payments.php'>Go to Bill Payments page</a></p>";

$conn->close();
?>
