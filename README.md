# Gogol Analytics Dashboard

A real-time web analytics dashboard with visitor tracking, bot detection, and event monitoring.

## Features

- **Real-time Traffic Dashboard**: Stacked bar chart showing bots, new visitors, and returning visitors
- **Live Visitor Table**: Real-time event stream with detailed tooltips (0ms delay)
- **Event Tracking**: Comprehensive data collection including:
  - Timestamp, page URL, event type
  - Visitor ID (hashed IP + User Agent)
  - Geolocation (country from browser locale)
  - OS, browser, device type
  - Screen resolution, timezone
  - Page load time
  - Bot detection
- **Easy Integration**: Copy-paste script snippet for external websites
- **E2E Testing**: Full Playwright test suite included

## Tech Stack

- **Backend**: Express.js with SQLite database
- **Frontend**: React with Radix UI components
- **Charts**: Recharts for data visualization
- **Testing**: Playwright for E2E tests

## Quick Start

### 1. Install Dependencies

```bash
# Install root dependencies
npm install

# Install frontend dependencies
cd frontend
npm install
cd ..

# Install E2E test dependencies
cd tests/e2e
npm install
cd ../..
```

### 2. Build Frontend

```bash
cd frontend
npm run build
cd ..
```

### 3. Start Backend Server

```bash
npm start
```

The server will start on `http://localhost:3000`

## Development

### Run Frontend in Dev Mode

```bash
cd frontend
npm run dev
```

Frontend dev server runs on `http://localhost:5173` with proxy to backend.

### Run Backend in Dev Mode

```bash
npm run dev:backend
```

## Testing

### Test with Fake Website

Open `fake_website/index.html` in your browser. The tracking script is already embedded.

You can also serve it via the backend:
- Navigate to `http://localhost:3000/fake_website/index.html`

### Run E2E Tests

```bash
cd tests/e2e
npm test
```

Run tests in headed mode:
```bash
npm run test:headed
```

Run tests with UI:
```bash
npm run test:ui
```

## Project Structure

```
├── backend/
│   ├── server.js          # Express server with SSE
│   ├── db.js             # SQLite database operations
│   └── utils.js          # Bot detection, validation
├── frontend/
│   ├── src/
│   │   ├── App.jsx       # Main app component
│   │   ├── components/
│   │   │   ├── TrafficTab.jsx
│   │   │   ├── StackedBarChart.jsx
│   │   │   ├── LiveVisitorTable.jsx
│   │   │   └── SettingsTab.jsx
│   │   └── main.jsx
│   └── package.json
├── public/
│   ├── track.js          # Client-side tracking script
│   └── index.html        # Built frontend (after build)
├── fake_website/
│   ├── index.html
│   ├── about.html
│   └── products.html
├── tests/e2e/
│   ├── tests/
│   │   ├── dashboard.spec.js
│   │   ├── settings.spec.js
│   │   ├── tracking.spec.js
│   │   └── e2e-flow.spec.js
│   └── playwright.config.js
└── package.json
```

## API Endpoints

### POST /api/events
Receive tracking events from monitored websites.

**Request Body:**
```json
{
  "timestamp": 1704067200000,
  "event_type": "pageview",
  "page": "https://example.com/page",
  "referrer": "https://google.com",
  "country": "US",
  "os": "Windows",
  "browser": "Chrome 120",
  "device_type": "PC",
  "resolution": "1920x1080",
  "timezone": "America/New_York",
  "page_load": 1250,
  "user_agent": "Mozilla/5.0..."
}
```

### GET /api/events/stream
Server-Sent Events endpoint for real-time updates.

### GET /api/traffic/aggregated
Get aggregated traffic data for charts.

**Query Parameters:**
- `bucket`: "hour", "day", or "week"
- `start`: Start timestamp in milliseconds
- `end`: End timestamp in milliseconds

### GET /api/events/recent
Get recent events for live table.

**Query Parameters:**
- `limit`: Number of events to return (default: 50)

### GET /api/script
Get the tracking script snippet for embedding.

## Environment Variables

Create a `.env` file (see `.env.example`):

```
PORT=3000
DATABASE_PATH=./analytics.db
ALLOWED_ORIGINS=*
RATE_LIMIT_WINDOW=60000
RATE_LIMIT_MAX=100
```

## Embedding the Tracking Script

1. Go to the Settings tab in the dashboard
2. Copy the script snippet
3. Paste it in your website's `<head>` section

Example:
```html
<script src="http://your-domain:3000/track.js"></script>
<script>
  if (window.initAnalytics) {
    window.initAnalytics({ endpoint: 'http://your-domain:3000/api/events' });
  }
</script>
```

## Data Collection

The tracking script automatically collects:
- Page views and navigation
- Button clicks
- Link clicks
- Device and browser information
- Geographic data (from browser)
- Performance metrics (page load time)
- Bot detection

All visitor IDs are hashed (SHA-256) combinations of IP and User Agent.

## Bot Detection

Automatically detects bots based on User Agent patterns:
- googlebot, bingbot, crawler, spider
- headless browsers (puppeteer, playwright, selenium)
- Social media crawlers (facebookexternalhit, twitterbot)

## License

MIT
