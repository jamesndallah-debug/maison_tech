<?php
include 'dp.php';

// Add description column to products table
$sql = "ALTER TABLE products ADD COLUMN description TEXT AFTER category_id";
if ($conn->query($sql) === TRUE) {
    echo "Column 'description' added successfully to 'products' table.\n";
} else {
    // If column already exists, it's fine
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Column 'description' already exists in 'products' table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

// Update existing products with a default description if needed
$conn->query("UPDATE products SET description = 'High-quality technology solution from Maison Tech.' WHERE description IS NULL OR description = ''");

echo "Database update complete.";
?>