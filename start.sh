#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Kill any existing PHP servers on port 8080
echo -e "${BLUE}Stopping any existing PHP servers...${NC}"
lsof -ti:8080 | xargs kill -9 2>/dev/null

# Start PHP server with router
echo -e "${GREEN}Starting PHP server with router on port 8080...${NC}"
php -S localhost:8080 router.php &
PHP_PID=$!

# Wait for server to start
sleep 2

# Open dashboard in browser
echo -e "${GREEN}Opening dashboard in browser...${NC}"
open http://localhost:8080/

# Wait a moment before opening test site
sleep 1

# Open test website in browser
echo -e "${GREEN}Opening test website in browser...${NC}"
open http://localhost:8080/fake_website/

echo -e "${BLUE}Server running with PID: $PHP_PID${NC}"
echo -e "${BLUE}Press Ctrl+C to stop the server${NC}"

# Keep script running and handle Ctrl+C
trap "echo -e '\n${BLUE}Stopping PHP server...${NC}'; kill $PHP_PID 2>/dev/null; exit" INT

# Wait for the PHP process
wait $PHP_PID