-- Add invoice_generated column
ALTER TABLE stock_requests ADD COLUMN invoice_generated INTEGER DEFAULT 0;

-- Add invoice_number column
ALTER TABLE stock_requests ADD COLUMN invoice_number TEXT;
