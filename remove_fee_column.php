<?php
include 'dp.php';

echo "<h2>Removing Fee Column from Transactions</h2>";

// Check if fee column exists and remove it
$check_column = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'fee'");
if ($check_column->num_rows > 0) {
    echo "<p style='color: orange;'>⚠ Fee column exists. Removing it...</p>";
    
    // Remove the fee column
    $sql = "ALTER TABLE money_transactions DROP COLUMN fee";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Fee column removed successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error removing fee column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Fee column does not exist (already removed)</p>";
}

echo "<p><a href='payments.php'>Go to Bill Payments page</a></p>";

$conn->close();
?>
