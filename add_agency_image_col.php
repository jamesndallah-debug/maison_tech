<?php
include 'dp.php';

// Add agency_service_image column to about_us table
$sql = "ALTER TABLE about_us ADD COLUMN agency_service_image VARCHAR(255) AFTER address";
if ($conn->query($sql) === TRUE) {
    echo "Column 'agency_service_image' added successfully to 'about_us' table.\n";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Column 'agency_service_image' already exists in 'about_us' table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

// Set default value if empty
$conn->query("UPDATE about_us SET agency_service_image = 'https://images.unsplash.com/photo-1553413077-190dd305871c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' WHERE agency_service_image IS NULL OR agency_service_image = ''");

echo "Database update for agency image complete.";
?>