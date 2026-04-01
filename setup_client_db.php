<?php
include 'dp.php';

$tables = [
    "CREATE TABLE IF NOT EXISTS client_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        client_email VARCHAR(255) NOT NULL,
        client_phone VARCHAR(50) NOT NULL,
        product_id INT DEFAULT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        product_description TEXT,
        region VARCHAR(100) NOT NULL,
        order_type ENUM('catalog', 'custom') DEFAULT 'catalog',
        status ENUM('pending', 'approved', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        agency_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        total_payable DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS about_us (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) DEFAULT 'Maison Tech',
        description TEXT,
        vision TEXT,
        mission TEXT,
        contact_email VARCHAR(255),
        contact_phone VARCHAR(255),
        address TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS official_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL,
        image_path VARCHAR(255),
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

// Insert default About Us content if empty
$check = $conn->query("SELECT id FROM about_us LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO about_us (description, vision, mission, contact_email, contact_phone, address) VALUES 
    ('Maison Tech is your premier destination for high-quality technology solutions.', 
    'To be the leading tech provider in the region.', 
    'To make technology feel at home for everyone.', 
    'info@maisontech.com', 
    '+123456789', 
    'Main Tech Plaza, Silicon Valley')");
}

echo "Database setup for client side complete.";
?>