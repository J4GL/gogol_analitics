#!/bin/bash

# Traffic Analytics Development Server Runner
# Starts PHP server on port 8000 and Python server on port 8001 for testing

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if a port is in use
check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        return 0  # Port is in use
    else
        return 1  # Port is free
    fi
}

# Function to kill processes on specific ports
kill_port() {
    local port=$1
    local pids=$(lsof -ti:$port 2>/dev/null)
    if [ ! -z "$pids" ]; then
        print_warning "Killing existing processes on port $port"
        echo $pids | xargs kill -9 2>/dev/null || true
        sleep 1
    fi
}

# Function to cleanup on exit
cleanup() {
    print_status "Shutting down servers..."
    kill_port 8000
    kill_port 8001
    print_success "Servers stopped"
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM

print_status "Starting Traffic Analytics Development Environment"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP 8.3 or higher."
    exit 1
fi

# Check if Python is available
if ! command -v python3 &> /dev/null && ! command -v python &> /dev/null; then
    print_error "Python is not installed. Please install Python 3."
    exit 1
fi

# Determine Python command
PYTHON_CMD="python3"
if ! command -v python3 &> /dev/null; then
    PYTHON_CMD="python"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")
if [ "$PHP_VERSION" -lt 80300 ]; then
    print_warning "PHP 8.3+ recommended. Current version: $(php -v | head -n 1)"
fi

# Check if test directory exists
if [ ! -d "test" ]; then
    print_error "Test directory not found. Please create test pages manually."
    print_status "You can create a simple test page with tracking script at test/index.html"
    exit 1
fi

# Kill any existing processes on the ports
kill_port 8000
kill_port 8001

# Start PHP server in background
print_status "Starting PHP server on http://localhost:8000"
php -S localhost:8000 -t public/ > /dev/null 2>&1 &
PHP_PID=$!

# Wait a moment for PHP server to start
sleep 2

# Check if PHP server started successfully
if ! check_port 8000; then
    print_error "Failed to start PHP server on port 8000"
    exit 1
fi

print_success "PHP server started (PID: $PHP_PID)"

# Start Python server in background
print_status "Starting Python server on http://localhost:8001"
cd test
$PYTHON_CMD -m http.server 8001 > /dev/null 2>&1 &
PYTHON_PID=$!
cd ..

# Wait a moment for Python server to start
sleep 2

# Check if Python server started successfully
if ! check_port 8001; then
    print_error "Failed to start Python server on port 8001"
    kill $PHP_PID 2>/dev/null || true
    exit 1
fi

print_success "Python server started (PID: $PYTHON_PID)"

# Display information
echo
print_success "Development environment is ready!"
echo
echo -e "${GREEN}📊 Analytics Dashboard:${NC} http://localhost:8000"
echo -e "${GREEN}🧪 Test Page:${NC}          http://localhost:8001"
echo -e "${GREEN}⚙️  Settings:${NC}           http://localhost:8000/settings"
echo
print_status "Press Ctrl+C to stop both servers"
echo

# Wait for user interrupt
wait
