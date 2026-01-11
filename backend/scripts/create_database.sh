#!/bin/bash

# ============================================
# PostgreSQL Database Creation Script (Linux/Mac)
# ============================================
# This script helps create the PostgreSQL database
# ============================================

echo "============================================"
echo "Driving School Database Setup"
echo "============================================"
echo ""

# Check if PostgreSQL is available
if ! command -v psql &> /dev/null; then
    echo "ERROR: PostgreSQL (psql) is not found in PATH."
    echo "Please install PostgreSQL or add it to your system PATH."
    exit 1
fi

echo "PostgreSQL found."
echo ""

# Prompt for database name
read -p "Enter database name (default: driving_school): " DB_NAME
DB_NAME=${DB_NAME:-driving_school}

# Prompt for username
read -p "Enter PostgreSQL username (default: postgres): " DB_USER
DB_USER=${DB_USER:-postgres}

# Prompt for host
read -p "Enter PostgreSQL host (default: localhost): " DB_HOST
DB_HOST=${DB_HOST:-localhost}

echo ""
echo "============================================"
echo "Creating database: $DB_NAME"
echo "User: $DB_USER"
echo "Host: $DB_HOST"
echo "============================================"
echo ""

# Create the database
echo "Creating database..."
psql -U "$DB_USER" -h "$DB_HOST" -c "CREATE DATABASE $DB_NAME;" postgres

if [ $? -ne 0 ]; then
    echo ""
    echo "ERROR: Failed to create database."
    echo "The database might already exist, or there was a connection error."
    exit 1
fi

echo "Database created successfully!"
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Run the SQL script
echo "Creating tables and schema..."
psql -U "$DB_USER" -h "$DB_HOST" -d "$DB_NAME" -f "$SCRIPT_DIR/../sql/create_database.sql"

if [ $? -ne 0 ]; then
    echo ""
    echo "ERROR: Failed to create tables."
    echo "Please check the error messages above."
    exit 1
fi

echo ""
echo "============================================"
echo "Database setup complete!"
echo "============================================"
echo ""
echo "Database: $DB_NAME"
echo "You can now connect to the database using:"
echo "  psql -U $DB_USER -h $DB_HOST -d $DB_NAME"
echo ""

