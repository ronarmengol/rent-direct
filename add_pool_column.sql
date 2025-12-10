-- Add swimming pool column to properties table
ALTER TABLE properties ADD COLUMN has_pool TINYINT(1) DEFAULT 0 AFTER bedrooms;
