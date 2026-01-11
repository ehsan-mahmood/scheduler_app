# How to Find PostgreSQL Port from pgAdmin

## Method 1: Server Properties (Easiest)

1. **Open pgAdmin**
2. **In the left panel, find your PostgreSQL server** (e.g., "PostgreSQL 16")
3. **Right-click on the server name**
4. **Select "Properties"**
5. **Click on the "Connection" tab**
6. **Look at the "Port" field** - this is your PostgreSQL port (usually 5432)

## Method 2: SQL Query in pgAdmin

1. **Connect to your server in pgAdmin**
2. **Right-click on any database** (e.g., "postgres")
3. **Select "Query Tool"**
4. **Run this SQL:**
   ```sql
   SHOW port;
   ```
5. The result will show the port number

Or run:
```sql
SELECT name, setting FROM pg_settings WHERE name = 'port';
```

## Method 3: Check Connection String

1. In pgAdmin, look at your server connection details
2. The connection string or server info usually shows the port
3. Look for something like: `localhost:5432` or `127.0.0.1:5432`

## Common Ports

- **5432** - Default PostgreSQL port (most common)
- **5433** - Alternative port (if 5432 is in use)
- **5434, 5435** - Other alternatives

## Update config.php

Once you know your port, update `backend/config.php`:

```php
define('DB_PORT', '5432'); // Change to your actual port
```

## Important Note

If you're getting "Connection refused" error, it's usually NOT a port issue, but:

1. **PostgreSQL service not running** - Start it in Services (services.msc)
2. **TCP/IP not enabled** - PostgreSQL might be using named pipes only
3. **Wrong password** - Check your DB_PASS in config.php

The port is usually correct (5432) - the problem is more likely the service not running or TCP/IP not enabled.

