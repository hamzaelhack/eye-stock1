-- Add company information columns to users table
ALTER TABLE users ADD COLUMN company_name TEXT;
ALTER TABLE users ADD COLUMN address TEXT;
ALTER TABLE users ADD COLUMN nif TEXT;
ALTER TABLE users ADD COLUMN nic TEXT;
ALTER TABLE users ADD COLUMN art TEXT;
