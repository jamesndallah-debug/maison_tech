<?php
include 'dp.php';

echo "<h2>Adding bank_name Column to money_transactions Table</h2>";

try {
    // Check if bank_name column already exists
    $check_column = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'bank_name'");
    
    if ($check_column->num_rows == 0) {
        // Add bank_name column to money_transactions table
        $sql = "ALTER TABLE money_transactions ADD COLUMN bank_name VARCHAR(50) AFTER payment_service";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Successfully added bank_name column to money_transactions table</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding bank_name column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ bank_name column already exists in money_transactions table</p>";
    }
    
    // Verify column was added
    $verify = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'bank_name'");
    if ($verify->num_rows > 0) {
        echo "<p style='color: green;'>✓ bank_name column is now available in money_transactions table</p>";
    } else {
        echo "<p style='color: red;'>❌ bank_name column verification failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<p><a href="money_transactions.php" class="btn btn-primary">Go to Money Transactions</a></p>

<style>
    .btn-primary { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    .btn-primary:hover { background: #0056b3; }
</style>
