<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap rule:
// - If an admin already exists, setup is admin-only.
// - If no admin exists yet (fresh environment), allow setup to initialize the system.
$bootstrap_allowed = false;

$probe = @new mysqli("localhost", "root", "", "maison_tech");
if (!$probe->connect_error) {
    $tbl = $probe->query("SHOW TABLES LIKE 'employees'");
    if (!$tbl || $tbl->num_rows === 0) {
        $bootstrap_allowed = true;
    } else {
        $adminCheck = $probe->query("SELECT COUNT(*) AS c FROM employees WHERE role='admin'");
        $adminCount = (int)($adminCheck->fetch_assoc()['c'] ?? 0);
        $bootstrap_allowed = ($adminCount === 0);
    }
    $probe->close();
} else {
    // Database may not exist yet, allow first-time setup.
    $bootstrap_allowed = true;
}

if (
    !$bootstrap_allowed &&
    (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')
) {
    http_response_code(403);
    echo "<div style='font-family: sans-serif; padding: 14px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; color: #9b2c2c;'>Access Denied. Admins only.</div>";
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";

// Create connection to MySQL (without database name)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("<div style='color:red; font-family: sans-serif;'>Connection failed: " . $conn->connect_error . "</div>");
}

echo "<h1 style='font-family: sans-serif;'>Maison Tech Database Setup</h1>";

// 1. Create Database if it doesn't exist
$dbName = "maison_tech";
if ($conn->query("CREATE DATABASE IF NOT EXISTS $dbName")) {
    echo "<p style='color:green; font-family: sans-serif;'>Step 1: Database '$dbName' ready.</p>";
} else {
    die("<p style='color:red; font-family: sans-serif;'>Step 1 Error: " . $conn->error . "</p>");
}

// 2. Select the database
$conn->select_db($dbName);

// 3. Load and execute SQL file
$sqlFile = 'database.sql';
if (!file_exists($sqlFile)) {
    die("<div style='color:red; font-family: sans-serif;'>Step 3 Error: database.sql not found! Please ensure it is in the same folder as this script.</div>");
}

$sql = file_get_contents($sqlFile);

// Remove the CREATE DATABASE and USE lines from the SQL string to avoid conflicts with Step 1 & 2
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS maison_tech;/i', '', $sql);
$sql = preg_replace('/USE maison_tech;/i', '', $sql);

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        // Store result set to clear the buffer
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    if ($conn->errno) {
        echo "<div style='color:red; font-family: sans-serif;'>Step 3 Error during table creation: " . $conn->error . "</div>";
    } else {
        echo "<p style='color:green; font-family: sans-serif;'>Step 3: Tables and default data created successfully.</p>";
        echo "<hr>";
        echo "<div style='color:green; font-weight:bold; font-size: 1.2em; font-family: sans-serif;'>Setup Complete!</div>";
        echo "<p style='font-family: sans-serif;'>You can now go to the <a href='login.php' style='color: #007bff; text-decoration: underline;'>Login Page</a>.</p>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; font-family: sans-serif; display: inline-block;'>";
        echo "<strong>Default Admin Credentials:</strong><br>";
        echo "Username: <span style='color: #d63384;'>admin</span><br>";
        echo "Password: <span style='color: #d63384;'>password</span>";
        echo "</div>";
    }
} else {
    echo "<div style='color:red; font-family: sans-serif;'>Step 3 Error: " . $conn->error . "</div>";
}

$conn->close();
?>