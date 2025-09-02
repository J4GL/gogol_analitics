#!/bin/bash

echo "======================================"
echo "    Gogol Analytics Setup Script     "
echo "======================================"
echo ""

# Check for PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 7.4 or higher."
    exit 1
fi
echo "✅ PHP is installed: $(php -v | head -n 1)"

# Check if PHP has SQLite support
if ! php -m | grep -i sqlite > /dev/null; then
    echo "❌ PHP SQLite extension is not installed. Please install php-sqlite3."
    exit 1
fi
echo "✅ PHP SQLite extension is available"

# Check for Node.js (optional, for tests)
if command -v node &> /dev/null; then
    echo "✅ Node.js is installed: $(node -v)"
else
    echo "⚠️  Node.js is not installed (optional, needed for tests)"
fi

echo ""
echo "Setting up SQLite database..."

# Create database directory if it doesn't exist
if [ ! -d "database" ]; then
    mkdir -p database
    echo "✅ Created database directory"
fi

# Initialize SQLite database
sqlite3 database/analytics.db < database/schema.sql

if [ $? -eq 0 ]; then
    echo "✅ SQLite database created successfully"
    chmod 666 database/analytics.db
    chmod 755 database
    echo "✅ Database permissions set"
else
    echo "❌ Database setup failed. Please check the schema file."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cat > .env << EOL
# Database Configuration (SQLite)
DB_PATH=database/analytics.db

# Security
HASH_SALT=gogol_secret_salt_$(date +%s)
API_KEY=gogol_api_key_$(openssl rand -hex 16)

# Application
APP_URL=http://localhost:8000
DEBUG=false
EOL
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi

# Install npm dependencies if package.json exists and npm is available
if [ -f package.json ] && command -v npm &> /dev/null; then
    echo ""
    echo "Installing npm dependencies..."
    npm install
    echo "✅ Dependencies installed"
fi

echo ""
echo "======================================"
echo "        Setup Complete!              "
echo "======================================"
echo ""
echo "To start the application:"
echo "  php -S localhost:8000"
echo ""
echo "To run the test website:"
echo "  php -S localhost:8001 -t fake_website"
echo ""
echo "To run tests (requires npm):"
echo "  npm test"
echo ""
echo "Dashboard URL: http://localhost:8000"
echo "Test Site URL: http://localhost:8001"
echo ""
echo "SQLite database is ready to use - no additional configuration needed!"