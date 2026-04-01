CREATE DATABASE IF NOT EXISTS maison_tech;

USE maison_tech;

-- Drop existing tables if they exist to start fresh
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
    money_daily_closing,
    money_transactions,
    money_float_opening,
    money_cash_opening,
    sale_items,
    sales,
    stock_movements,
    activity_logs,
    products,
    employees,
    categories;
SET FOREIGN_KEY_CHECKS = 1;

-- Employees Table (used for login and activity)
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'chairman', 'manager', 'money_agent') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user (password: password)
INSERT INTO employees (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Product Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Stock Movements
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_change INT NOT NULL, -- Positive for IN, Negative for OUT
    movement_type VARCHAR(50) NOT NULL, -- e.g., 'New Stock', 'Sale', 'Correction'
    movement_reason VARCHAR(255) DEFAULT NULL,
    user_id INT NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Sales Table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Mobile Money Wallet', 'Bank') NOT NULL DEFAULT 'Cash',
    payment_provider VARCHAR(50) DEFAULT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Sale Items
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Employee Activity Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Salary Payments Table
CREATE TABLE salary_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    notes TEXT,
    processed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employees(id) ON DELETE CASCADE
);

-- Mobile Money Transactions (Tanzania)
CREATE TABLE money_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tx_type ENUM('cash_in', 'cash_out') NOT NULL,
    provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'bank_agency', 'other') NOT NULL DEFAULT 'other',
    amount DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    customer_msisdn VARCHAR(20),
    customer_name VARCHAR(100),
    payment_service VARCHAR(50),
    bank_name VARCHAR(50),
    reference VARCHAR(80),
    notes VARCHAR(255),
    tx_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Mobile Money Opening Balances
CREATE TABLE money_cash_opening (
    id TINYINT PRIMARY KEY,
    opening_cash DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO money_cash_opening (id, opening_cash) VALUES (1, 0.00);

CREATE TABLE money_float_opening (
    provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'bank_agency', 'other') PRIMARY KEY,
    opening_float DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO money_float_opening (provider, opening_float) VALUES
('mpesa',0.00),('mixx_by_yass',0.00),('airtelmoney',0.00),('halopesa',0.00),('kingamuzi',0.00),('government',0.00),('bank_agency',0.00),('other',0.00);

-- Banks for Bank Agency transactions
CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(50) NOT NULL UNIQUE,
    bank_code VARCHAR(10) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default banks
INSERT INTO banks (bank_name, bank_code) VALUES 
('CRDB', 'CRDB'),
('NMB', 'NMB'),
('NBC', 'NBC'),
('Selcom', 'SELC'),
('TCB', 'TCB');

-- End of day reconciliation (daily closing)
CREATE TABLE money_daily_closing (
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
    expected_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    counted_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    variance_float_kingamuzi DECIMAL(12, 2) NOT NULL,
    expected_float_government DECIMAL(12, 2) NOT NULL,
    counted_float_government DECIMAL(12, 2) NOT NULL,
    variance_float_government DECIMAL(12, 2) NOT NULL,
    expected_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    counted_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    variance_float_airtelmoney DECIMAL(12, 2) NOT NULL,
    expected_float_halopesa DECIMAL(12, 2) NOT NULL,
    counted_float_halopesa DECIMAL(12, 2) NOT NULL,
    variance_float_halopesa DECIMAL(12, 2) NOT NULL,
    expected_float_bank_agency DECIMAL(12, 2) NOT NULL,
    counted_float_bank_agency DECIMAL(12, 2) NOT NULL,
    variance_float_bank_agency DECIMAL(12, 2) NOT NULL,
    expected_float_other DECIMAL(12, 2) NOT NULL,
    counted_float_other DECIMAL(12, 2) NOT NULL,
    variance_float_other DECIMAL(12, 2) NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_closing_date (closing_date)
);
