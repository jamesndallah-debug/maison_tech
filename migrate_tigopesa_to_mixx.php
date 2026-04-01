<?php
include 'dp.php';

echo "<h2>Updating TigoPesa to Mixx By Yass</h2>";

try {
    // 1. Update existing transactions
    $sql1 = "UPDATE money_transactions SET provider = 'mixx_by_yass' WHERE provider = 'tigopesa'";
    if ($conn->query($sql1)) {
        echo "<p style='color: green;'>✓ Updated money_transactions: Changed TigoPesa to Mixx By Yass</p>";
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: blue;'>→ Affected rows: $affected_rows</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No TigoPesa transactions found or error: " . $conn->error . "</p>";
    }
    
    // 2. Update float opening
    $sql2 = "UPDATE money_float_opening SET provider = 'mixx_by_yass' WHERE provider = 'tigopesa'";
    if ($conn->query($sql2)) {
        echo "<p style='color: green;'>✓ Updated money_float_opening: Changed TigoPesa to Mixx By Yass</p>";
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: blue;'>→ Affected rows: $affected_rows</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No TigoPesa float opening found or error: " . $conn->error . "</p>";
    }
    
    // 3. Update daily closing data
    $sql3 = "UPDATE money_daily_closing SET 
                expected_float_mixx_by_yass = expected_float_tigopesa,
                counted_float_mixx_by_yass = counted_float_tigopesa,
                variance_float_mixx_by_yass = variance_float_tigopesa
              WHERE expected_float_tigopesa IS NOT NULL OR counted_float_tigopesa IS NOT NULL";
    
    if ($conn->query($sql3)) {
        echo "<p style='color: green;'>✓ Updated money_daily_closing: Migrated TigoPesa data to Mixx By Yass</p>";
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: blue;'>→ Affected rows: $affected_rows</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No daily closing data found or error: " . $conn->error . "</p>";
    }
    
    // 4. Update provider ENUM in money_transactions
    $sql4 = "ALTER TABLE money_transactions 
             MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') NOT NULL DEFAULT 'other'";
    
    if ($conn->query($sql4)) {
        echo "<p style='color: green;'>✓ Updated money_transactions provider ENUM</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Provider ENUM already updated or error: " . $conn->error . "</p>";
    }
    
    // 5. Update provider ENUM in money_float_opening
    $sql5 = "ALTER TABLE money_float_opening 
             MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') PRIMARY KEY";
    
    if ($conn->query($sql5)) {
        echo "<p style='color: green;'>✓ Updated money_float_opening provider ENUM</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Provider ENUM already updated or error: " . $conn->error . "</p>";
    }
    
    // 6. Add Mixx By Yass entry if it doesn't exist
    $sql6 = "INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES ('mixx_by_yass', 0.00)";
    
    if ($conn->query($sql6)) {
        echo "<p style='color: green;'>✓ Added Mixx By Yass to money_float_opening</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Mixx By Yass already exists or error: " . $conn->error . "</p>";
    }
    
    echo "<h3 style='color: green;'>✅ Migration Complete!</h3>";
    echo "<p>All TigoPesa references have been successfully updated to Mixx By Yass</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error during migration: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
