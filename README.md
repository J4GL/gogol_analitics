# Traffic Analytics - PHP MVC Web Analytics System

A minimal PHP MVC web application with SQLite that collects page events via GET requests (base64 JSON), displays a D3 stacked 24-hour hourly chart, shows live events, and restricts dashboard access by authorized IPs.

## Features

✅ **Data Collection**
- JavaScript tracking script with automatic page load detection
- Base64 JSON payload via GET requests to avoid CORS issues
- Noscript fallback with 1x1 GIF pixel
- Bot detection (webdriver, user agent patterns)
- Client-side browser/OS detection
- Timezone-based country mapping

✅ **Dashboard**
- IP-based access control with CIDR support
- D3.js stacked bar chart showing 24-hour traffic patterns
- Unique visitors, returning visitors, and bot traffic segmentation
- Live events table with Server-Sent Events (SSE)
- Settings page with copyable tracking snippet

✅ **Backend**
- PHP 8.4+ MVC architecture
- SQLite database with proper indexing
- RESTful API endpoints
- Privacy-focused (no IP storage, only hashed visitor IDs)
- CORS headers for cross-origin tracking

## Quick Start

1. **Setup**
   ```bash
   ./setup.sh
   ```

2. **Start Development Environment**
   ```bash
   ./run.sh
   ```
   This starts:
   - PHP server on http://localhost:8000 (Analytics Dashboard)
   - Python server on http://localhost:8001 (Test Pages)

3. **Access Dashboard**
   - Open http://localhost:8000
   - Dashboard is accessible from localhost by default

4. **Test Tracking**
   - Open http://localhost:8001 (test pages with tracking)
   - Watch events appear in the dashboard in real-time

5. **Add Tracking to Your Website**
   - Go to http://localhost:8000/settings
   - Copy the tracking snippet
   - Add it to your website's HTML

## Configuration

Edit `.env` file:
```env
DATABASE_PATH=./database.sqlite
AUTHORIZED_IPS=127.0.0.1,::1,10.0.0.0/8,192.168.0.0/16,172.16.0.0/12,YOUR_IP_HERE
DOMAIN=http://localhost:8000
```

## API Endpoints

- `GET /api/collect?data=<base64json>` - Collect tracking events
- `GET /api/stats?hours=24` - Get hourly statistics
- `GET /collect.gif?data=<base64json>` - Noscript fallback
- `GET /collect.js` - JavaScript tracking script
- `GET /events` - Server-Sent Events stream (dashboard only)

## Tracking Data Collected

- Page URL and referrer
- Visitor timezone and derived country
- Screen resolution
- Browser and operating system
- Page load time
- Bot detection status
- Anonymous visitor ID (MD5 hash of IP + User Agent)

## Privacy & Security

- **No IP Storage**: Only hashed visitor IDs are stored
- **IP Whitelisting**: Dashboard access restricted by IP/CIDR ranges
- **Bot Filtering**: Automatic detection and filtering of bot traffic
- **Data Retention**: Events older than 7 days are cleaned up
- **Input Validation**: All inputs are sanitized and validated

## Database Schema

```sql
CREATE TABLE events (
    id TEXT NOT NULL,              -- MD5 hash of IP + User Agent
    ts_ns INTEGER NOT NULL,        -- Server timestamp (nanoseconds)
    client_ts_ns INTEGER,          -- Client timestamp (nanoseconds)
    page TEXT,                     -- Page path
    referrer TEXT,                 -- Referrer URL
    timezone TEXT,                 -- Client timezone
    country TEXT,                  -- Derived from timezone
    os TEXT,                       -- Operating system
    browser TEXT,                  -- Browser name
    resolution TEXT,               -- Screen resolution
    page_load_ms INTEGER,          -- Page load time
    is_bot INTEGER DEFAULT 0,      -- Bot detection flag
    user_agent TEXT,               -- User agent (truncated)
    raw_payload TEXT               -- Debug payload (optional)
);
```

## Requirements

- PHP 8.3+ with SQLite3 extension
- Modern web browser with JavaScript enabled
- Write permissions for database file

## Development

### Development Scripts

- `./setup.sh` - Initialize database and check requirements
- `./run.sh` - Start both PHP and Python development servers
  - Automatically creates test pages if they don't exist
  - Handles port conflicts and cleanup
  - Press Ctrl+C to stop both servers

### Architecture

The application follows a simple MVC pattern:
- `public/index.php` - Front controller
- `app/Controllers/` - Request handlers
- `app/Models/` - Database operations
- `app/Views/` - HTML templates
- `config/` - Configuration and routes
## Testing

Test the tracking by:
1. Adding the tracking script to your website pages
2. Visiting your website to generate real events
3. Checking the dashboard for new events
4. Verifying the API with curl:
   ```bash
   curl "http://localhost:8000/api/collect?data=$(echo '{"page":"/your-page"}' | base64)"
   ```

## Production Deployment

1. Set up a proper web server (Apache/Nginx)
2. Configure HTTPS
3. Update `DOMAIN` in `.env` to your production URL
4. Add your server IPs to `AUTHORIZED_IPS`
5. Set up log rotation and monitoring
6. Consider using Redis for SSE event streaming

## License

This project is open source and available under the MIT License.
