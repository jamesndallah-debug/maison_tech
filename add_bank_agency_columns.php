<?php
include 'dp.php';

echo "<h2>Adding Bank Agency Columns to money_daily_closing Table</h2>";

try {
    // Check if bank_agency columns already exist
    $check_columns = $conn->query("SHOW COLUMNS FROM money_daily_closing LIKE '%bank_agency%'");
    
    if ($check_columns->num_rows == 0) {
        // Add bank_agency columns to money_daily_closing table
        $sql = "ALTER TABLE money_daily_closing 
                ADD COLUMN expected_float_bank_agency DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_halopesa,
                ADD COLUMN counted_float_bank_agency DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_halopesa,
                ADD COLUMN variance_float_bank_agency DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_halopesa";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Successfully added bank_agency columns to money_daily_closing table</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding bank_agency columns: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ bank_agency columns already exist in money_daily_closing table</p>";
    }
    
    // Verify columns were added
    $verify = $conn->query("SHOW COLUMNS FROM money_daily_closing LIKE '%bank_agency%'");
    if ($verify->num_rows > 0) {
        echo "<p style='color: green;'>✓ bank_agency columns are now available in money_daily_closing table</p>";
        echo "<ul>";
        while($row = $verify->fetch_assoc()) {
            echo "<li>" . $row['Field'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ bank_agency columns verification failed</p>";
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
