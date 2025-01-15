-- Add status column to products table
ALTER TABLE products ADD COLUMN status VARCHAR(20) DEFAULT 'active' NOT NULL;
