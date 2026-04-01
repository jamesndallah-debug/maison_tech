-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
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
);

-- Create expense_categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default expense categories
INSERT INTO expense_categories (name, description) VALUES 
('Rent', 'Monthly rent payments for office space'),
('Food', 'Food and refreshments for staff and meetings'),
('Transport', 'Transportation fees and travel expenses'),
('Website Management', 'Website hosting, domain, and maintenance costs'),
('Offerings', 'Business offerings and charitable contributions'),
('Staff Expenses', 'Staff-related expenses and allowances'),
('Utilities', 'Electricity, water, and other utility bills'),
('Office Supplies', 'Stationery, equipment, and office supplies'),
('Marketing', 'Advertising and promotional expenses'),
('Other', 'Miscellaneous expenses not covered by other categories');
