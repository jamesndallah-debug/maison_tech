<?php
/**
 * Database Setup Script for Hostinger
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your Hostinger hosting
 * 2. Update the database credentials below FIRST
 * 3. Run this script by visiting: yourdomain.com/setup_database.php
 * 4. Delete this file after successful setup!
 */

// ========================================
// STEP 1: UPDATE THESE CREDENTIALS FIRST!
// ========================================
$servername = "localhost";
$username = "u123456789_maison";  // Your Hostinger MySQL username
$password = "YourPassword123!";   // Your Hostinger MySQL password
$dbname = "u123456789_maison_tech"; // Your Hostinger database name
// ========================================

echo "<!DOCTYPE html>
<html>
<head>
    <title>Maison Tech - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #2c3e50; }
        h2 { color: #34495e; margin-top: 30px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-left: 4px solid #3498db; }
    </style>
</head>
<body>";

echo "<h1>🚀 Maison Tech Database Setup</h1>";

// Check if credentials are still default
if ($username === "u123456789_maison" || $password === "YourPassword123!") {
    echo "<div class='error'>";
    echo "<strong>⚠️ STOP!</strong> You must update the database credentials in this file first!<br><br>";
    echo "Open <code>setup_database.php</code> in a text editor and update lines 16-18 with your actual Hostinger database credentials.";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "<div class='info'>";
echo "<strong>Database Configuration:</strong><br>";
echo "Server: $servername<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "</div>";

// Create connection (without database first)
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<div class='error'>";
    echo "<strong>❌ Connection Failed:</strong> " . $conn->connect_error . "<br><br>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Verify your database credentials are correct<br>";
    echo "2. Ensure the database exists in Hostinger hPanel<br>";
    echo "3. Check that the user has been created and has privileges<br>";
    echo "4. Contact Hostinger support if issues persist";
    echo "</div>";
    exit;
}

echo "<div class='success'><strong>✅ Connected to MySQL server successfully!</strong></div>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<div class='success'><strong>✅ Database '$dbname' created or verified!</strong></div>";
} else {
    echo "<div class='error'><strong>❌ Error creating database:</strong> " . $conn->error . "</div>";
    exit;
}

// Select the database
$conn->select_db($dbname);

// Read and execute the SQL file
$sql_file = __DIR__ . '/maison_tech.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>";
    echo "<strong>❌ SQL file not found!</strong><br>";
    echo "Please ensure <code>maison_tech.sql</code> is in the same directory as this script.";
    echo "</div>";
    exit;
}

echo "<div class='info'><strong>📄 Found SQL file:</strong> " . basename($sql_file) . "</div>";

$sql_content = file_get_contents($sql_file);

// Split into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<div class='step'>";
echo "<h2>Executing Queries...</h2>";

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '--') === 0) continue;
    
    if ($conn->query($query) === TRUE) {
        $success_count++;
    } else {
        $error_count++;
        $errors[] = $conn->error;
    }
}

echo "</div>";

// Results summary
if ($error_count === 0) {
    echo "<div class='success'>";
    echo "<strong>🎉 Database setup completed successfully!</strong><br><br>";
    echo "✅ Executed $success_count queries successfully<br>";
    echo "✅ All tables created<br>";
    echo "✅ Default admin user created<br>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Delete this setup file immediately!</strong> (security)</li>";
    echo "<li>Login to your application at: <code>https://yourdomain.com/login.php</code></li>";
    echo "<li>Default credentials:<br>";
    echo "&nbsp;&nbsp;&nbsp;Username: <strong>admin</strong><br>";
    echo "&nbsp;&nbsp;&nbsp;Password: <strong>password</strong></li>";
    echo "<li><strong>IMPORTANT:</strong> Change the admin password immediately after login!</li>";
    echo "<li>Configure your system settings and add employees</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Security Reminder:</strong> Delete <code>setup_database.php</code> now to prevent unauthorized database access!";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<strong>⚠️ Setup completed with errors</strong><br><br>";
    echo "✅ Successful queries: $success_count<br>";
    echo "❌ Failed queries: $error_count<br><br>";
    echo "<strong>Errors encountered:</strong><ul>";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    if (count($errors) > 5) {
        echo "<li>... and " . (count($errors) - 5) . " more errors</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Some tables may already exist (this is normal)<br>";
    echo "2. Check if all required tables were created in phpMyAdmin<br>";
    echo "3. You can manually import maison_tech.sql via phpMyAdmin if needed<br>";
    echo "4. Contact Hostinger support for assistance";
    echo "</div>";
}

// Verify essential tables
echo "<div class='step'>";
echo "<h2>Verifying Tables...</h2>";

$required_tables = [
    'employees',
    'categories',
    'products',
    'sales',
    'sale_items',
    'money_transactions',
    'money_cash_opening',
    'money_float_opening',
    'settings'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<div class='success'><strong>✅ All essential tables are present!</strong></div>";
} else {
    echo "<div class='error'>";
    echo "<strong>⚠️ Missing tables:</strong><br>";
    echo implode(', ', $missing_tables);
    echo "<br><br>You may need to manually import the SQL file via phpMyAdmin.";
    echo "</div>";
}

echo "</div>";

$conn->close();

echo "</body></html>";
?>
