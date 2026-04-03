<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$probe = @new mysqli("localhost", "root", "", "maison_tech");
$bootstrap_allowed = false;
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
    $bootstrap_allowed = true;
}

if (
    !$bootstrap_allowed &&
    (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')
) {
    http_response_code(403);
    echo "<h3 style='font-family: sans-serif; color: #b91c1c;'>Access Denied. Admins only.</h3>";
    exit;
}

$conn = new mysqli("localhost", "root", "");
$conn->query("CREATE DATABASE IF NOT EXISTS maison_tech");
$conn->select_db("maison_tech");

$messages = [];
$ok = function ($msg) use (&$messages) { $messages[] = ['ok', $msg]; };
$err = function ($msg) use (&$messages) { $messages[] = ['err', $msg]; };

try {
    // Core tables
    $conn->query("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff', 'chairman', 'manager', 'money_agent') NOT NULL DEFAULT 'staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        price DECIMAL(10, 2) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        category_id INT,
        image_url VARCHAR(255),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        quantity_change INT NOT NULL,
        movement_type VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_method ENUM('Cash', 'Mobile Money Wallet', 'Bank') NOT NULL DEFAULT 'Cash',
        payment_provider VARCHAR(50) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price_per_unit DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // Money module tables
    $conn->query("CREATE TABLE IF NOT EXISTS money_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tx_type ENUM('cash_in', 'cash_out') NOT NULL,
        provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'other') NOT NULL DEFAULT 'other',
        amount DECIMAL(10, 2) NOT NULL,
        fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        commission DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        customer_msisdn VARCHAR(20),
        reference VARCHAR(80),
        notes VARCHAR(255),
        tx_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS money_cash_opening (
        id TINYINT PRIMARY KEY,
        opening_cash DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $conn->query("INSERT IGNORE INTO money_cash_opening (id, opening_cash) VALUES (1, 0.00)");

    $conn->query("CREATE TABLE IF NOT EXISTS money_float_opening (
        provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'other') PRIMARY KEY,
        opening_float DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $conn->query("INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES
        ('mpesa',0.00),('mixx_by_yass',0.00),('airtelmoney',0.00),('halopesa',0.00),('other',0.00)");

    $conn->query("CREATE TABLE IF NOT EXISTS money_daily_closing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        closing_date DATE NOT NULL,
        user_id INT NOT NULL,
        expected_cash DECIMAL(12, 2) NOT NULL,
        counted_cash DECIMAL(12, 2) NOT NULL,
        variance_cash DECIMAL(12, 2) NOT NULL,
        expected_float_mpesa DECIMAL(12, 2) NOT NULL,
        counted_float_mpesa DECIMAL(12, 2) NOT NULL,
        variance_float_mpesa DECIMAL(12, 2) NOT NULL,
        expected_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
        counted_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
        variance_float_mixx_by_yass DECIMAL(12, 2) NOT NULL,
        expected_float_airtelmoney DECIMAL(12, 2) NOT NULL,
        counted_float_airtelmoney DECIMAL(12, 2) NOT NULL,
        variance_float_airtelmoney DECIMAL(12, 2) NOT NULL,
        expected_float_halopesa DECIMAL(12, 2) NOT NULL,
        counted_float_halopesa DECIMAL(12, 2) NOT NULL,
        variance_float_halopesa DECIMAL(12, 2) NOT NULL,
        expected_float_other DECIMAL(12, 2) NOT NULL,
        counted_float_other DECIMAL(12, 2) NOT NULL,
        variance_float_other DECIMAL(12, 2) NOT NULL,
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_closing_date (closing_date)
    )");

    // Ensure at least one admin exists
    $adminCount = (int)($conn->query("SELECT COUNT(*) AS c FROM employees WHERE role='admin'")->fetch_assoc()['c'] ?? 0);
    if ($adminCount === 0) {
        $username = 'admin';
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO employees (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute();
        $stmt->close();
        $ok("Created default admin user (username: admin, password: password).");
    }

    $ok("Migration complete. All required tables are present.");
} catch (Throwable $e) {
    $err("Migration failed: " . $e->getMessage());
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maison Tech Migration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; }
        .ok { color: #166534; }
        .err { color: #991b1b; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Migration Result</h2>
        <ul>
            <?php foreach ($messages as $m): ?>
                <li class="<?php echo htmlspecialchars($m[0]); ?>"><?php echo htmlspecialchars($m[1]); ?></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="login.php">Go to Login</a></p>
    </div>
</body>
</html>

