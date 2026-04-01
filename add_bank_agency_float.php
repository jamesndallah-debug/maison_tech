<?php
include 'dp.php';

echo "<h2>Adding Bank Agency to money_float_opening Table</h2>";

try {
    // Check if bank_agency provider already exists in ENUM
    $check_enum = $conn->query("SHOW COLUMNS FROM money_float_opening WHERE Field = 'provider'");
    
    if ($check_enum->num_rows > 0) {
        $row = $check_enum->fetch_assoc();
        $enum_values = $row['Type'];
        
        // Check if bank_agency is already in the ENUM
        if (strpos($enum_values, 'bank_agency') === false) {
            // Update ENUM to include bank_agency
            $new_enum = str_replace(
                "ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other')",
                "ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'bank_agency', 'other')",
                $enum_values
            );
            
            $sql = "ALTER TABLE money_float_opening MODIFY COLUMN provider $new_enum PRIMARY KEY";
            
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✓ Successfully added bank_agency to money_float_opening provider ENUM</p>";
            } else {
                echo "<p style='color: red;'>❌ Error updating money_float_opening ENUM: " . $conn->error . "</p>";
            }
            
            // Add bank_agency entry if it doesn't exist
            $insert_sql = "INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES ('bank_agency', 0.00)";
            if ($conn->query($insert_sql)) {
                echo "<p style='color: green;'>✓ Added bank_agency entry to money_float_opening</p>";
            } else {
                echo "<p style='color: orange;'>⚠ bank_agency entry already exists</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ bank_agency already exists in money_float_opening provider ENUM</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not find provider column in money_float_opening table</p>";
    }
    
    // Verify the update
    $verify = $conn->query("SELECT * FROM money_float_opening WHERE provider = 'bank_agency'");
    if ($verify->num_rows > 0) {
        echo "<p style='color: green;'>✓ bank_agency is now available in money_float_opening table</p>";
    } else {
        echo "<p style='color: red;'>❌ bank_agency verification failed</p>";
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
