# Gogol Analytics

A lightweight, privacy-first web analytics solution built with PHP and JavaScript.

## Features

- **Real-time visitor tracking** - See who's on your site right now
- **Privacy-focused** - No cookies required, GDPR compliant
- **Bot detection** - Automatically identifies and filters bot traffic
- **Visitor categorization** - Distinguishes between new, returning visitors, and bots
- **Interactive dashboard** - Stacked bar charts and live visitor feed
- **Easy integration** - Simple script tag installation
- **Self-hosted** - Complete control over your data

## Requirements

- PHP 7.4 or higher with SQLite extension
- Node.js 16+ (for running tests, optional)
- Modern web browser

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/gogol_analytics.git
cd gogol_analytics
```

### 2. Run setup script (recommended)
```bash
chmod +x setup.sh
./setup.sh
```

**Or manually:**

### 2a. Setup the SQLite database
```bash
mkdir -p database
sqlite3 database/analytics.db < database/schema.sql
chmod 666 database/analytics.db
```

### 2b. Create environment configuration
```bash
cp .env.example .env
```

The default SQLite configuration in `.env`:
```
# Database Configuration (SQLite)
DB_PATH=database/analytics.db
```

### 3. Start the application
```bash
php -S localhost:8000
```

Visit http://localhost:8000 to view the dashboard.

## Usage

### Adding tracking to your website

Add the following code to your website's HTML, just before the closing `</body>` tag:

```html
<!-- Gogol Analytics Tracking Code -->
<script>
(function() {
    window.GOGOL_API_URL = 'https://your-domain.com/api/collect.php';
    var script = document.createElement('script');
    script.src = 'https://your-domain.com/assets/js/tracker.js';
    script.async = true;
    document.head.appendChild(script);
})();
</script>
<!-- End Gogol Analytics -->
```

### Tracking custom events

You can track custom events using the global `gogol` object:

```javascript
// Track a custom event
gogol.trackEvent('button_click', {
    button: 'signup',
    location: 'header'
});

// Track form submission
gogol.trackEvent('form_submit', {
    form: 'contact',
    email: 'user@example.com'
});
```

## Testing

### Install test dependencies
```bash
npm install
```

### Run the test website
```bash
npm run serve:test
```
Visit http://localhost:8001 to see the test website.

### Run E2E tests
```bash
# Run tests with visible browser windows
npm test

# Run tests in UI mode
npm run test:ui

# Run tests in headless mode
npx playwright test --headed=false
```

## Dashboard Features

### Traffic Tab
- **Visitor Statistics** - Total, new, returning visitors and bots
- **Stacked Bar Chart** - Visual representation of visitor types over time
- **Live Visitor Feed** - Real-time list of current visitors with detailed tooltips
- **Top Pages** - Most visited pages on your site
- **Top Referrers** - Where your traffic comes from

### Settings Tab
- **Tracking Code** - Copy-paste installation code
- **What We Track** - List of collected data points
- **Privacy Information** - GDPR compliance details

## Data Collected

- Visitor ID (browser fingerprint)
- Page views and events
- Country (from browser locale)
- Operating System
- Browser
- Device type (PC/Mobile)
- Screen resolution
- Timezone
- Page load time
- Referrer
- Bot status

## Privacy & Security

- No personal data collection
- No cookies required (uses localStorage for sessions)
- All data stays on your server
- IP addresses are hashed for privacy
- GDPR compliant
- Bot traffic automatically identified

## Development

### Project Structure
```
gogol_analytics/
├── api/               # Backend API endpoints
├── assets/            # Frontend assets (CSS, JS)
├── database/          # Database schema
├── fake_website/      # Test website
├── tests/            # E2E tests
└── index.php         # Main dashboard
```

### API Endpoints

- `POST /api/collect.php` - Collect tracking data
- `GET /api/dashboard.php?action=stats` - Get visitor statistics
- `GET /api/dashboard.php?action=live` - Get live visitor data
- `GET /api/dashboard.php?action=chart` - Get chart data

## Troubleshooting

### Database connection issues
- Verify SQLite database file exists: `ls -la database/analytics.db`
- Check database permissions: `chmod 666 database/analytics.db`
- Ensure database directory is writable: `chmod 755 database/`

### Tracking not working
- Check browser console for errors
- Verify API URL in tracking code
- Ensure CORS headers are properly set
- Check PHP error logs

### Dashboard not updating
- Verify data is being collected in database
- Check browser console for API errors
- Ensure auto-refresh is enabled (10-second interval)

## License

MIT License - See LICENSE file for details

## Contributing

Pull requests are welcome! Please ensure all tests pass before submitting.

## Support

For issues and questions, please open a GitHub issue.