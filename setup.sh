#!/bin/bash

# Traffic Analytics Setup Script

echo "Setting up Traffic Analytics..."

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed. Please install PHP 7.3 or higher."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")
if [ "$PHP_VERSION" -lt 70300 ]; then
    echo "Error: PHP 7.3 or higher is required. Current version: $(php -v | head -n 1)"
    exit 1
fi

# Check if SQLite extension is available
if ! php -m | grep -q sqlite3; then
    echo "Error: PHP SQLite3 extension is not installed."
    exit 1
fi

# Create database if it doesn't exist
if [ ! -f "database.sqlite" ]; then
    echo "Creating SQLite database..."
    php -r "
    \$db = new PDO('sqlite:database.sqlite');
    \$db->exec('CREATE TABLE events (
        id TEXT NOT NULL,
        ts_ns INTEGER NOT NULL,
        client_ts_ns INTEGER,
        page TEXT,
        referrer TEXT,
        timezone TEXT,
        country TEXT,
        os TEXT,
        browser TEXT,
        resolution TEXT,
        page_load_ms INTEGER,
        is_bot INTEGER DEFAULT 0,
        user_agent TEXT,
        raw_payload TEXT
    )');
    \$db->exec('CREATE INDEX idx_events_ts ON events(ts_ns)');
    \$db->exec('CREATE INDEX idx_events_id ON events(id)');
    echo 'Database created successfully.\n';
    "
else
    echo "Database already exists."
fi

# Set permissions
chmod 644 database.sqlite
chmod 755 public/

echo "Setup complete!"
echo "You can now start a PHP development server with:"
echo "php -S localhost:8000 -t public/"
