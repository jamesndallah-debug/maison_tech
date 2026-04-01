<?php
include 'dp.php';

// Add movement_reason column to stock_movements table if it doesn't exist
$query = "ALTER TABLE stock_movements ADD COLUMN movement_reason VARCHAR(255) DEFAULT NULL AFTER movement_type";

if ($conn->query($query)) {
    echo "Successfully added 'movement_reason' column to 'stock_movements' table.";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
