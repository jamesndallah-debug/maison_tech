-- Update database schema to support Bill Payments (Kingamuzi TV and Government payments)

-- Add kingamuzi and government to the provider ENUM in money_transactions table
ALTER TABLE money_transactions 
MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') NOT NULL DEFAULT 'other';

-- Add customer_name field for bill payments
ALTER TABLE money_transactions 
ADD COLUMN customer_name VARCHAR(100) AFTER customer_msisdn;

-- Update money_float_opening table to include the new providers
ALTER TABLE money_float_opening 
MODIFY COLUMN provider ENUM('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'kingamuzi', 'government', 'other') PRIMARY KEY;

-- Insert opening balances for the new providers (0.00 as default)
INSERT IGNORE INTO money_float_opening (provider, opening_float) VALUES 
('kingamuzi', 0.00),
('government', 0.00);

-- Update money_daily_closing table to include the new providers
ALTER TABLE money_daily_closing 
ADD COLUMN expected_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_mixx_by_yass,
ADD COLUMN counted_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_kingamuzi,
ADD COLUMN variance_float_kingamuzi DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_kingamuzi,
ADD COLUMN expected_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER variance_float_kingamuzi,
ADD COLUMN counted_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER expected_float_government,
ADD COLUMN variance_float_government DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER counted_float_government;
