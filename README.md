# Visitor Tracking Dashboard

A comprehensive PHP-based web application for tracking website visitors with real-time analytics. This system provides detailed insights into visitor behavior, engagement patterns, and website performance through an intuitive dashboard interface.

## 🚀 Features

- **Real-time Visitor Tracking** - Monitor visitor activity as it happens
- **Comprehensive Analytics** - Detailed visitor statistics and behavior analysis
- **Visitor Classification** - Automatic categorization of new, returning, and bot visitors
- **Event Tracking** - Track page views, clicks, scrolls, and custom events
- **Live Dashboard** - Interactive charts and real-time visitor feed
- **Privacy-First** - IP hashing, no PII collection, GDPR compliant
- **Easy Integration** - Simple JavaScript snippet installation

## 🛠 Technology Stack

### Backend
- **PHP 8.1+** - Server-side application logic
- **SQLite** - Lightweight database for analytics storage
- **Composer** - Dependency management

### Frontend
- **HTML5/CSS3** - Dashboard interface
- **JavaScript (Vanilla)** - Client-side tracking and interactions
- **Chart.js** - Data visualization
- **Bootstrap 5** - Responsive UI framework

### Testing
- **PHPUnit** - Unit testing framework
- **Playwright** - End-to-end testing (configurable)

## 📦 Installation

### Prerequisites
- PHP 8.1 or higher
- SQLite extension
- Composer

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd gogol_analitics
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Initialize the database**
   ```bash
   php init_database.php
   ```

4. **Start the development server**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Access the dashboard**
   Open your browser and navigate to: `http://localhost:8000`

## 🎯 Usage

### Dashboard Access
Visit `http://localhost:8000` to access the analytics dashboard with the following sections:

- **Traffic Tab** - Visitor analytics with stacked bar charts
- **Pages Tab** - Page performance and referrer statistics
- **Live Tab** - Real-time visitor activity feed
- **Settings Tab** - Tracking script generation and configuration

### Website Integration

1. Go to the Settings tab in the dashboard
2. Enter your website URL
3. Generate the tracking code
4. Copy and paste the code into your website's `<head>` section

Example tracking code:
```html
<!-- Visitor Tracking by Gogol Analytics -->
<script>
  (function() {
    var script = document.createElement('script');
    script.src = 'http://localhost:8000/assets/js/tracker.js';
    script.setAttribute('data-api-url', 'http://localhost:8000/api/track.php');
    script.setAttribute('data-site-url', 'https://yourwebsite.com');
    script.async = true;
    document.head.appendChild(script);
  })();
</script>
```

### Testing with Demo Website

A complete test website is included at `http://localhost:8000/fake_website/` with:
- Multiple pages for navigation testing
- Interactive elements for event tracking
- Form submissions and custom events
- Real-time tracking demonstration

## 📊 API Endpoints

### Tracking Endpoint
**POST** `/api/track.php`
```json
{
  "visitor_uuid": "uuid-string",
  "event_type": "pageview|click|scroll|custom",
  "page_url": "https://example.com/page",
  "page_title": "Page Title",
  "timestamp": 1640995200000
}
```

### Analytics Endpoint
**GET** `/api/analytics.php?period=daily&format=analytics`

### Live Visitors Endpoint
**GET** `/api/live-visitors.php?minutes=5&type=feed`

### Additional Data Endpoint
**GET** `/api/data.php?action=pages|visitor|attribution|trends`

## 🏗 Project Structure

```
/
├── public/                    # Web-accessible files
│   ├── index.php             # Dashboard main page
│   ├── api/                  # API endpoints
│   │   ├── track.php         # Visitor tracking
│   │   ├── analytics.php     # Analytics data
│   │   ├── live-visitors.php # Real-time feed
│   │   └── data.php          # Additional data
│   └── assets/               # Static assets
│       ├── css/dashboard.css # Dashboard styles
│       └── js/
│           ├── dashboard.js  # Dashboard functionality
│           └── tracker.js    # Tracking script
├── src/                      # PHP application code
│   ├── Controllers/          # Request controllers
│   ├── Models/               # Data models (Visitor, Event)
│   ├── Services/             # Business logic services
│   └── Database/             # Database connection and setup
├── fake_website/             # Demo website for testing
│   ├── index.html           # Demo home page
│   ├── about.html           # Demo about page
│   ├── services.html        # Demo services page
│   ├── contact.html         # Demo contact page
│   └── assets/              # Demo website assets
├── config/                   # Configuration files
│   ├── app.php              # Application settings
│   └── database.php         # Database configuration
├── storage/                  # Data storage
│   └── analytics.sqlite     # SQLite database
└── tests/                    # Test files
    ├── e2e/                 # End-to-end tests
    └── unit/                # Unit tests
```

## 🔧 Configuration

### Application Settings (`config/app.php`)
- Server configuration
- Rate limiting settings
- Session timeout
- CORS origins
- Debug mode

### Database Settings (`config/database.php`)
- SQLite configuration
- Performance optimizations
- Journal mode settings

## 📈 Analytics Features

### Visitor Classification
- **New Visitors** - First-time visitors to the website
- **Returning Visitors** - Previously seen visitors
- **Bots** - Automated traffic and web crawlers

### Event Types
- **Page Views** - Page navigation and visits
- **Click Events** - Button and link interactions
- **Scroll Events** - Page scroll behavior
- **Custom Events** - Developer-defined tracking

### Chart Visualizations
- Stacked bar charts showing visitor types over time
- Real-time visitor activity feed
- Page performance metrics
- Referrer source analysis

## 🔒 Privacy & Security

- **IP Address Hashing** - All IP addresses are hashed for anonymity
- **No PII Collection** - No personally identifiable information stored
- **Do Not Track Compliance** - Respects browser DNT settings
- **Rate Limiting** - API request throttling to prevent abuse
- **Input Validation** - Comprehensive data validation and sanitization
- **CORS Configuration** - Secure cross-origin request handling

## 🧪 Testing

### Run Unit Tests
```bash
composer test
```

### Demo Website Testing
1. Visit `http://localhost:8000/fake_website/`
2. Navigate between pages
3. Click buttons and interact with elements
4. View real-time updates in the dashboard

### Manual API Testing
```bash
# Test analytics endpoint
curl http://localhost:8000/api/analytics.php

# Test tracking endpoint
curl -X POST -H "Content-Type: application/json" \
  -d '{"visitor_uuid":"test-uuid","event_type":"pageview","page_url":"http://test.com","timestamp":1640995200000}' \
  http://localhost:8000/api/track.php
```

## 🚀 Production Deployment

### Requirements
- Web server (Apache/Nginx) with PHP 8.1+
- SSL certificate for HTTPS
- Proper file permissions for storage directory

### Security Considerations
1. Set `debug_mode => false` in `config/app.php`
2. Configure proper CORS origins
3. Set up appropriate rate limiting
4. Use HTTPS for all tracking requests
5. Regularly backup the SQLite database

### Performance Optimization
- Enable PHP OPcache
- Configure SQLite WAL mode (already enabled)
- Use a reverse proxy for static assets
- Implement proper caching headers

## 📚 Documentation

### Custom Event Tracking
```javascript
// Track custom events from your website
if (window.gogolAnalytics) {
    window.gogolAnalytics.track('button_click', {
        button_name: 'Subscribe',
        section: 'Newsletter'
    });
}
```

### Visitor Information
```javascript
// Get visitor information
const visitorUuid = window.gogolAnalytics.getVisitorUuid();
const sessionId = window.gogolAnalytics.getSessionId();
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For questions, issues, or feature requests:
1. Check the documentation above
2. Review the demo website examples
3. Examine the API endpoint responses
4. Test with the included fake website

## 🔄 Changelog

### Version 1.0.0
- Initial release with complete visitor tracking system
- Real-time analytics dashboard
- Comprehensive API endpoints
- Demo website for testing
- Privacy-compliant data handling
- Full documentation and setup guide