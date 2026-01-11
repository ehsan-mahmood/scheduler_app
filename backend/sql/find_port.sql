-- Run this SQL query in pgAdmin Query Tool to find your PostgreSQL port
SHOW port;

-- Also check server settings
SELECT name, setting FROM pg_settings WHERE name = 'port';

