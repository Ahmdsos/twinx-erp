-- TWINX ERP Database Initialization
-- This script runs automatically when the PostgreSQL container starts

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable full-text search for Arabic
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Enable unaccent for better search
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Grant all privileges
GRANT ALL PRIVILEGES ON DATABASE twinx_erp TO twinx_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO twinx_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO twinx_user;

-- Set default timezone
SET timezone = 'UTC';

-- Log successful initialization
DO $$
BEGIN
    RAISE NOTICE 'TWINX ERP database initialized successfully!';
END $$;
