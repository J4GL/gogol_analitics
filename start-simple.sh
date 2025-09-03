#!/bin/bash

# Simple start script - works with ANY web server
echo "==================================="
echo "    Gogol Analytics - Simple Start"
echo "==================================="

# Kill any existing PHP servers on port 8080
lsof -ti:8080 | xargs kill -9 2>/dev/null

# Start PHP server WITHOUT router - direct file access
echo "Starting PHP server (no router needed)..."
php -S localhost:8080 &
PHP_PID=$!

sleep 2

echo ""
echo "✅ Server running on http://localhost:8080"
echo ""
echo "Dashboard: http://localhost:8080/index.php"
echo "API Test: http://localhost:8080/collect.php"
echo ""
echo "This setup works with ANY web server:"
echo "- Apache (no .htaccess needed)"
echo "- Nginx (no config needed)"
echo "- PHP built-in server (no router needed)"
echo ""
echo "Press Ctrl+C to stop"

trap "kill $PHP_PID 2>/dev/null; exit" INT

wait $PHP_PID