<?php
include 'dp.php';

// Modify client_email to be NULLable
$sql = "ALTER TABLE client_orders MODIFY COLUMN client_email VARCHAR(255) NULL";
if ($conn->query($sql) === TRUE) {
    echo "Column 'client_email' in 'client_orders' table modified to allow NULL values.\n";
} else {
    echo "Error modifying column: " . $conn->error . "\n";
}

echo "Database update for optional email complete.";
?>