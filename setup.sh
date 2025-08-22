#!/bin/bash

# Visitor Tracking Dashboard Setup Script
# This script sets up the complete development environment for the analytics dashboard

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Global variables for script options
DEV_MODE=false
PROD_MODE=false
NO_TEST=false

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

print_header() {
    echo -e "\n${BLUE}===================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===================================${NC}\n"
}

# Function to show help
show_help() {
    echo "Visitor Tracking Dashboard Setup Script"
    echo ""
    echo "USAGE:"
    echo "  ./setup.sh [OPTIONS]"
    echo ""
    echo "OPTIONS:"
    echo "  -h, --help     Show this help message"
    echo "  --dev          Install development dependencies (PHPUnit)"
    echo "  --prod         Production mode (skip dev dependencies)"
    echo "  --no-test      Skip installing test dependencies"
    echo ""
    echo "DESCRIPTION:"
    echo "  This script sets up the complete development environment for"
    echo "  the Visitor Tracking Dashboard, including:"
    echo "  - System requirements validation"
    echo "  - PHP and Node.js dependency installation"
    echo "  - Database initialization"
    echo "  - File permissions setup"
    echo "  - Configuration testing"
    echo ""
    echo "EXAMPLES:"
    echo "  ./setup.sh              # Standard setup"
    echo "  ./setup.sh --dev        # Include development tools"
    echo "  ./setup.sh --no-test    # Skip test dependencies"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php_version() {
    if command_exists php; then
        local php_version=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
        local required_version="8.1"
        
        if [ "$(printf '%s\n' "$required_version" "$php_version" | sort -V | head -n1)" = "$required_version" ]; then
            print_success "PHP $php_version detected (>= $required_version required)"
            return 0
        else
            print_error "PHP $php_version detected, but PHP $required_version or higher is required"
            return 1
        fi
    else
        print_error "PHP is not installed"
        return 1
    fi
}

# Function to check Node.js version
check_node_version() {
    if command_exists node; then
        local node_version=$(node -v | sed 's/v//')
        local required_version="16.0.0"
        
        if [ "$(printf '%s\n' "$required_version" "$node_version" | sort -V | head -n1)" = "$required_version" ]; then
            print_success "Node.js $node_version detected (>= $required_version required)"
            return 0
        else
            print_error "Node.js $node_version detected, but Node.js $required_version or higher is required"
            return 1
        fi
    else
        print_error "Node.js is not installed"
        return 1
    fi
}

# Main setup function
main() {
    print_header "Visitor Tracking Dashboard Setup"
    
    print_status "Starting setup process..."
    print_status "Current directory: $(pwd)"
    
    # Check system requirements
    print_header "Checking System Requirements"
    
    local requirements_met=true
    
    # Check PHP
    if ! check_php_version; then
        requirements_met=false
        print_error "Please install PHP 8.1 or higher"
        echo "  - macOS: brew install php"
        echo "  - Ubuntu: sudo apt-get install php8.1 php8.1-sqlite3 php8.1-mbstring"
        echo "  - Windows: Download from https://windows.php.net/download/"
    fi
    
    # Check required PHP extensions
    if command_exists php; then
        print_status "Checking PHP extensions..."
        
        local required_extensions=("sqlite3" "pdo_sqlite" "mbstring" "json")
        for ext in "${required_extensions[@]}"; do
            if php -m | grep -q "^$ext$"; then
                print_success "PHP extension '$ext' is available"
            else
                print_error "PHP extension '$ext' is missing"
                requirements_met=false
            fi
        done
        
        # Special check for PDO (often built-in)
        if php -r "if (extension_loaded('pdo')) { echo 'PDO OK'; } else { echo 'PDO MISSING'; exit(1); }" 2>/dev/null | grep -q "PDO OK"; then
            print_success "PHP extension 'pdo' is available"
        else
            print_error "PHP extension 'pdo' is missing"
            requirements_met=false
        fi
    fi
    
    # Check Composer
    if command_exists composer; then
        print_success "Composer is available"
    else
        print_error "Composer is not installed"
        print_status "Please install Composer from https://getcomposer.org/download/"
        requirements_met=false
    fi
    
    # Check Node.js (optional but recommended for testing)
    if ! check_node_version; then
        print_warning "Node.js not found or version too old. Playwright tests will not be available."
        print_status "To install Node.js: https://nodejs.org/en/download/"
    fi
    
    # Check npm
    if command_exists npm; then
        print_success "npm is available"
    else
        print_warning "npm is not available. Playwright tests will not be available."
    fi
    
    if [ "$requirements_met" = false ]; then
        print_error "Some requirements are not met. Please install the missing dependencies and run this script again."
        exit 1
    fi
    
    # Install PHP dependencies
    print_header "Installing PHP Dependencies"
    
    if [ -f "composer.json" ]; then
        print_status "Installing Composer dependencies..."
        
        if [ "$DEV_MODE" = true ]; then
            composer install --optimize-autoloader
            print_success "PHP dependencies installed successfully (including dev dependencies)"
        elif [ "$PROD_MODE" = true ]; then
            composer install --no-dev --optimize-autoloader --no-interaction
            print_success "PHP dependencies installed successfully (production mode)"
        else
            composer install --no-dev --optimize-autoloader
            print_success "PHP dependencies installed successfully"
        fi
    else
        print_error "composer.json not found. Are you in the correct directory?"
        exit 1
    fi
    
    # Install Node.js dependencies (if available and not skipped)
    if [ "$NO_TEST" != true ] && command_exists npm && [ -f "package.json" ]; then
        print_header "Installing Node.js Dependencies"
        print_status "Installing npm dependencies..."
        npm install
        print_success "Node.js dependencies installed successfully"
        
        # Install Playwright browsers
        print_status "Installing Playwright browsers..."
        npx playwright install
        print_success "Playwright browsers installed successfully"
    elif [ "$NO_TEST" = true ]; then
        print_warning "Skipping Node.js dependencies (--no-test flag)"
    fi
    
    # Initialize database
    print_header "Setting Up Database"
    
    if [ -f "init_database.php" ]; then
        print_status "Initializing SQLite database..."
        php init_database.php
        print_success "Database initialized successfully"
    else
        print_error "init_database.php not found"
        exit 1
    fi
    
    # Create storage directory if it doesn't exist
    if [ ! -d "storage" ]; then
        print_status "Creating storage directory..."
        mkdir -p storage
        print_success "Storage directory created"
    fi
    
    # Set proper permissions
    print_header "Setting Permissions"
    
    print_status "Setting permissions for storage directory..."
    chmod 755 storage
    if [ -f "storage/analytics.sqlite" ]; then
        chmod 664 storage/analytics.sqlite
    fi
    print_success "Permissions set successfully"
    
    # Verify fake_website location
    print_header "Verifying Project Structure"
    
    if [ -d "public/fake_website" ]; then
        print_success "Test website is correctly located in public/fake_website"
    else
        print_warning "Test website not found in public/fake_website"
        if [ -d "fake_website" ]; then
            print_status "Moving fake_website to public directory..."
            mv fake_website public/
            print_success "Test website moved to correct location"
        fi
    fi
    
    # Test database connection
    print_header "Testing Configuration"
    
    print_status "Testing database connection..."
    php -r "
    require_once 'vendor/autoload.php';
    try {
        \$pdo = VisitorTracking\Database\Connection::getInstance();
        \$stmt = \$pdo->query('SELECT COUNT(*) FROM visitors');
        \$count = \$stmt->fetchColumn();
        echo 'Database connection successful. Visitor count: ' . \$count . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    "
    print_success "Database connection test passed"
    
    # Create .gitignore if it doesn't exist
    if [ ! -f ".gitignore" ]; then
        print_status "Creating .gitignore file..."
        cat > .gitignore << EOF
# Dependencies
/vendor/
/node_modules/

# Environment files
.env
.env.local

# Database
/storage/analytics.sqlite
/storage/analytics.sqlite-*

# Test results
/test-results/
/playwright-report/

# Logs
*.log

# OS files
.DS_Store
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Build files
/dist/
/build/
EOF
        print_success ".gitignore file created"
    fi
    
    # Final success message
    print_header "Setup Complete!"
    
    print_success "Visitor Tracking Dashboard has been set up successfully!"
    echo ""
    print_status "Next steps:"
    echo "  1. Start the development server:"
    echo "     ${GREEN}php -S localhost:8000 -t public${NC}"
    echo ""
    echo "  2. Open your browser and visit:"
    echo "     ${GREEN}http://localhost:8000${NC} - Analytics Dashboard"
    echo "     ${GREEN}http://localhost:8000/fake_website/${NC} - Test Website"
    echo ""
    echo "  3. Generate test data by browsing the test website"
    echo ""
    if command_exists npm; then
        echo "  4. Run end-to-end tests (optional):"
        echo "     ${GREEN}npm run test:e2e${NC}"
        echo ""
    fi
    
    print_status "For more information, see README.md"
    print_success "Happy tracking! 🚀"
}

# Check if script is being run from the correct directory
if [ ! -f "composer.json" ] || [ ! -f "init_database.php" ]; then
    print_error "This script must be run from the project root directory"
    print_error "Make sure you're in the directory containing composer.json and init_database.php"
    exit 1
fi

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        --dev)
            DEV_MODE=true
            shift
            ;;
        --prod)
            PROD_MODE=true
            shift
            ;;
        --no-test)
            NO_TEST=true
            shift
            ;;
        *)
            print_error "Unknown option: $1"
            echo "Use ./setup.sh --help for usage information"
            exit 1
            ;;
    esac
done

# Run main function
main "$@"