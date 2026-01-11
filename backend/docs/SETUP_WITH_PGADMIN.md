# Setup Database Using pgAdmin

pgAdmin is the easiest way to set up the database! No command-line connection issues.

## Step 1: Open pgAdmin

1. Open pgAdmin from the Start menu (search for "pgAdmin 4")
2. When it opens, you'll see a server list in the left panel
3. Expand "Servers" if needed
4. You should see "PostgreSQL 16" (or your version) - click to expand it
5. You'll be prompted for the PostgreSQL password (the one you set during installation)
6. Enter your password and click OK

## Step 2: Create the Database

1. Right-click on "Databases" (under your PostgreSQL server)
2. Select **"Create"** → **"Database..."**
3. In the "Database" field, enter: `driving_school`
4. **Important:** Click on the **"Definition"** tab at the top
5. In the "Template" dropdown, select **`template0`** (this avoids locale issues)
6. In the "Collation" field, you can leave it empty or select **`C`** (this avoids locale errors)
7. In the "Character type" field, you can leave it empty or select **`C`**
8. Click **"Save"** (or press F6)

That's it! The database is created.

**Note:** If you get a locale error, using `template0` and `C` for collation/character type will fix it. The `C` locale always exists and avoids Windows locale issues.

## Step 3: Run the SQL Script

1. In pgAdmin, click on the **"driving_school"** database in the left panel (to select it)
2. Click on the **"Tools"** menu at the top
3. Select **"Query Tool"** (or press Alt+Shift+Q)
4. This opens a SQL query window
5. Click the **"Open File"** icon (folder icon) in the toolbar, or press Ctrl+O
6. Navigate to your backend folder and select **`create_database.sql`**
7. The SQL script will load in the query window
8. Click the **"Execute"** button (lightning bolt icon) or press F5
9. Wait for the script to complete - you should see "Query returned successfully" at the bottom

## Step 4: Verify Tables Were Created

1. In the left panel, expand the **"driving_school"** database
2. Expand **"Schemas"**
3. Expand **"public"**
4. Expand **"Tables"**
5. You should see all the tables:
   - students
   - instructors
   - lesson_types
   - lessons
   - payment_deposits
   - sms_notifications

## Alternative: Copy-Paste SQL Script

If the file open doesn't work:

1. Open `create_database.sql` in a text editor (Notepad, VS Code, etc.)
2. Select all (Ctrl+A) and copy (Ctrl+C)
3. In pgAdmin Query Tool, paste (Ctrl+V)
4. Click Execute (F5)

## Troubleshooting

**Error: "unrecognised lc_locale" or locale error:**
- This happens when PostgreSQL tries to use a locale that doesn't exist on Windows
- **Fix:** When creating the database:
  1. Use `template0` as the template (in Definition tab)
  2. Set Collation to `C`
  3. Set Character type to `C`
- The `C` locale always exists and works on all systems

**Can't connect to server in pgAdmin:**
- Make sure PostgreSQL service is running (use Services.msc)
- Check that you're using the correct password

**Script fails with errors:**
- Make sure you selected the "driving_school" database before running the script
- Check the error message - it might tell you what's wrong (e.g., table already exists)

**Tables not showing:**
- Right-click on "Tables" and select "Refresh" (F5)
- Make sure you're looking under: Servers → PostgreSQL 16 → Databases → driving_school → Schemas → public → Tables

## That's It!

Once the tables are created, your database is ready to use. You can now:
- View/edit data using pgAdmin
- Connect your PHP backend to this database
- Run queries and manage your data

pgAdmin is much easier than command-line for database management! 🎉

