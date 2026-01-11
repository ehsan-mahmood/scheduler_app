# How to Find PostgreSQL Port

## Method 1: Check in pgAdmin (Easiest)

1. **Open pgAdmin**
2. **In the left panel, right-click on your PostgreSQL server** (e.g., "PostgreSQL 16")
3. **Select "Properties"**
4. **Go to the "Connection" tab**
5. Look for **"Port"** field - it will show the port number (usually 5432)

## Method 2: Check PostgreSQL Configuration File

1. Find `postgresql.conf` file:
   - Usually at: `C:\Program Files\PostgreSQL\16\data\postgresql.conf`
   - Or: `C:\Program Files\PostgreSQL\15\data\postgresql.conf`

2. Open the file in a text editor
3. Search for: `port =`
4. The number after `=` is your port (default is 5432)

## Method 3: Check Running Services

Open Command Prompt and run:
```cmd
netstat -an | findstr 5432
```

If PostgreSQL is running and listening, you'll see something like:
```
TCP    0.0.0.0:5432           0.0.0.0:0              LISTENING
```

If you see nothing, PostgreSQL service might not be running.

## Method 4: Check from pgAdmin SQL Query

1. **Connect to PostgreSQL in pgAdmin**
2. **Open Query Tool** (Tools → Query Tool)
3. **Run this SQL:**
   ```sql
   SHOW port;
   ```
4. It will show the port number

## Common Ports

- **5432** - Default PostgreSQL port (most common)
- **5433** - Alternative port (sometimes used if 5432 is taken)
- **5434, 5435, etc.** - Other alternatives

## If Port is Different

If your PostgreSQL is using a different port (not 5432), update `backend/config.php`:

```php
define('DB_PORT', '5433'); // Change to your actual port
```

## Troubleshooting

**If connection is refused:**
1. Make sure PostgreSQL service is running (see `QUICK_START.md`)
2. Check if port matches in config.php
3. Try connecting via pgAdmin first to verify credentials

