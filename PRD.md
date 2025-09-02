# Product Requirements Document - Gogol Analytics

## Product Overview

Gogol Analytics is a lightweight, self-hosted web analytics solution that provides real-time visitor tracking and insights for websites. It solves the problem of expensive third-party analytics services while maintaining complete data ownership and privacy control.

**Target Users:** Website owners, developers, and small businesses who want simple, effective analytics without external dependencies or privacy concerns.

**Value Proposition:** Complete control over visitor data, real-time insights, easy integration, and zero recurring costs.

## Core Features

### 1. Real-time Visitor Tracking
- **What it does:** Captures detailed visitor information including location, device, browser, and behavior
- **How it works:** JavaScript tracking library collects data points and sends them to PHP backend via AJAX

### 2. Interactive Dashboard
- **What it does:** Displays visitor analytics through intuitive charts and tables
- **How it works:** Two-tab interface with traffic visualization and configuration settings

### 3. Visitor Categorization
- **What it does:** Automatically categorizes visitors as new, returning, or bots
- **How it works:** Uses IP + User Agent hashing for unique identification and bot detection algorithms

### 4. Live Activity Feed
- **What it does:** Shows real-time visitor activity with detailed tooltips
- **How it works:** Auto-refreshing table with hover-based detailed information display

### 5. Easy Integration
- **What it does:** Provides simple script snippet for website integration
- **How it works:** Copy-paste JavaScript code generated in settings tab

## Development Order

1. **Foundation Layer:** Database schema, project structure, security configuration
2. **Data Collection:** JavaScript tracking library and PHP data ingestion API
3. **Data Processing:** Backend analytics calculations and visitor categorization
4. **User Interface:** Dashboard with charts, tables, and settings
5. **Testing:** Test website and comprehensive E2E testing with Playwright

## Project Structure

```
gogol_analytics/
├── api/
│   ├── collect.php          # Data collection endpoint
│   ├── dashboard.php        # Dashboard data API
│   └── config.php          # Database configuration
├── assets/
│   ├── css/
│   │   └── dashboard.css    # Dashboard styling
│   ├── js/
│   │   ├── tracker.js       # Client tracking library
│   │   ├── dashboard.js     # Dashboard functionality
│   │   └── chart.js         # Chart visualization
├── database/
│   └── schema.sql           # Database structure
├── fake_website/
│   ├── index.html          # Test website
│   └── pages/              # Additional test pages
├── tests/
│   └── e2e/
│       └── analytics.spec.js # Playwright tests
├── index.php               # Main dashboard
├── .env                    # Environment configuration
├── .gitignore             # Git ignore file
└── README.md              # Documentation
```