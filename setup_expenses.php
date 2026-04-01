<?php
include 'dp.php';

// Create expense_categories table
$sql1 = "CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create expenses table
$sql2 = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_category_id INT,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (recorded_by) REFERENCES employees(id)
)";

// Insert default categories
$sql3 = "INSERT INTO expense_categories (name, description) VALUES 
('Rent', 'Monthly rent payments for office space'),
('Food', 'Food and refreshments for staff and meetings'),
('Transport', 'Transportation fees and travel expenses'),
('Website Management', 'Website hosting, domain, and maintenance costs'),
('Offerings', 'Business offerings and charitable contributions'),
('Staff Expenses', 'Staff-related expenses and allowances'),
('Utilities', 'Electricity, water, and other utility bills'),
('Office Supplies', 'Stationery, equipment, and office supplies'),
('Marketing', 'Advertising and promotional expenses'),
('Other', 'Miscellaneous expenses not covered by other categories')";

// Execute queries
if ($conn->query($sql1)) {
    echo "✅ expense_categories table created successfully<br>";
} else {
    echo "❌ Error creating expense_categories table: " . $conn->error . "<br>";
}

if ($conn->query($sql2)) {
    echo "✅ expenses table created successfully<br>";
} else {
    echo "❌ Error creating expenses table: " . $conn->error . "<br>";
}

// Check if categories already exist
$check = $conn->query("SELECT COUNT(*) as count FROM expense_categories");
$count = $check->fetch_assoc()['count'];

if ($count == 0) {
    if ($conn->query($sql3)) {
        echo "✅ Default expense categories inserted successfully<br>";
    } else {
        echo "❌ Error inserting default categories: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Expense categories already exist<br>";
}

$conn->close();
echo "🎉 Expenses database setup complete!";
?>
