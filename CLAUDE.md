# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Gogol Analytics is a lightweight, privacy-focused web analytics solution built with PHP and JavaScript. It uses SQLite as its database and provides real-time visitor tracking with bot detection capabilities.

## Key Commands

### Development & Testing
```bash
# Start main application server with router
php -S localhost:8000 router.php

# Run Playwright tests (windows visible)
npm test

# Run tests in UI mode  
npm run test:ui

# Run specific test
npm run test:tracking
```

### Setup & Database
```bash
# Initial setup (creates database, .env file, installs dependencies)
chmod +x setup.sh
./setup.sh

# Manual database setup
sqlite3 database/analytics.db < database/schema.sql
chmod 666 database/analytics.db
```

## Architecture & Routing

### URL Structure
The application uses `router.php` for all routing:
- `/` → Dashboard (`index.php`)
- `/api/collect.php` → Data collection endpoint (`collect.php`)
- `/api/dashboard.php` → Dashboard data API (`dashboard.php`)
- `/api/config.php` → Configuration API (`config.php`)
- `/fake_website/*` → Test website for development
- `/assets/*` → Static assets (CSS, JS)

### Database Schema
SQLite database with three main tables:
- **visitors**: Unique visitor tracking with fingerprinting
- **events**: All visitor interactions (pageviews, custom events)
- **sessions**: Session management for visitor activity

### API Endpoints
All API endpoints are PHP files accessed through the router:
- `POST /api/collect.php` - Receives tracking data from tracker.js
- `GET /api/dashboard.php?action=stats` - Returns visitor statistics
- `GET /api/dashboard.php?action=live` - Returns live visitor data
- `GET /api/dashboard.php?action=chart` - Returns chart data for visualization

### Frontend Architecture
- **tracker.js**: Client-side tracking script that collects visitor data
- **dashboard.js**: Dashboard functionality including real-time updates, charts, and visitor feed
- Uses browser fingerprinting (no cookies) for visitor identification
- Auto-refreshes dashboard every 10 seconds

## Testing Approach

The project uses Playwright for E2E testing:
- Tests should run with visible browser windows (headless: false in config)
- Tests use the fake_website directory as test target
- No test mocks - tests run against actual PHP server
- Default timeouts: 3s for non-network, 13s for network operations

## Privacy & Security Considerations

- No personal data collection
- IP addresses are hashed for privacy
- Uses localStorage instead of cookies
- Bot detection built into tracking
- All data stays on self-hosted server
- GDPR compliant design

## Development Guidelines

1. PHP files in root directory handle API logic
2. Frontend assets in `/assets/js/` and `/assets/css/`
3. Test website in `/fake_website/` for development
4. Router handles all URL routing - never access PHP files directly
5. Always test tracking functionality with the fake_website
6. Use SQLite for all data storage needs