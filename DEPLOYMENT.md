# Production Deployment Guide

## ⚠️ Current Setup is Development Only

The current setup uses PHP's built-in server (`php -S localhost:8080 router.php`), which is **NOT suitable for production**. It's single-threaded, has no security features, and will crash under load.

## Production Server Requirements

### Option 1: Apache Server (Recommended)

Create `.htaccess` file in your document root:

```apache
RewriteEngine On

# Route API calls
RewriteRule ^api/(.*)$ api/$1.php [L,QSA]

# Route fake_website (remove in production)
RewriteRule ^fake_website/(.*)$ fake_website/$1 [L]

# Route assets
RewriteRule ^assets/(.*)$ assets/$1 [L]

# Route everything else to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

### Option 2: Nginx Server

Add to your nginx configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/gogol_analytics;
    index index.php;

    # API routes
    location ~ ^/api/(.*)$ {
        try_files $uri /api/$1.php?$query_string;
    }

    # Assets
    location /assets/ {
        try_files $uri =404;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Everything else to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## Security Considerations for Production

### 1. Environment Variables
Move sensitive data to environment variables:

```bash
# .env file (DO NOT commit to git)
DB_PATH=/var/www/data/analytics.db
DEBUG=false
ALLOWED_ORIGINS=https://yourdomain.com
API_RATE_LIMIT=100
```

### 2. Database Security
- Move SQLite database outside web root
- Set proper file permissions (640)
- Consider migrating to MySQL/PostgreSQL for high traffic

### 3. API Security
Add to `api/collect.php`:

```php
// CORS headers
$allowed_origins = explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// Rate limiting
session_start();
$requests = $_SESSION['api_requests'] ?? [];
$now = time();
$requests = array_filter($requests, fn($t) => $t > $now - 60);
if (count($requests) > ($_ENV['API_RATE_LIMIT'] ?? 100)) {
    http_response_code(429);
    exit('Rate limit exceeded');
}
$requests[] = $now;
$_SESSION['api_requests'] = $requests;
```

### 4. File Structure for Production
```
/var/www/yourdomain/          # Web root
├── index.php                 # Dashboard
├── .htaccess                 # Apache config
├── api/
│   ├── collect.php
│   ├── dashboard.php
│   └── config.php
├── assets/
│   ├── css/
│   └── js/
└── .env                      # Environment variables

/var/www/data/                # Outside web root
└── analytics.db              # Database file
```

### 5. Remove Development Files
Before deploying, remove:
- `fake_website/` directory
- `test-analytics.js`
- `start.sh`
- `router.php` (not needed with proper web server)
- `package.json`, `node_modules/`
- Any `.db` files in web root

## Deployment Checklist

- [ ] Set up proper web server (Apache/Nginx)
- [ ] Move database outside web root
- [ ] Configure environment variables
- [ ] Add CORS protection
- [ ] Implement rate limiting
- [ ] Set proper file permissions
- [ ] Enable HTTPS with SSL certificate
- [ ] Configure CSP headers
- [ ] Set up database backups
- [ ] Monitor error logs
- [ ] Test tracker on actual website
- [ ] Remove all development files

## Tracking Code for Production

Update the tracking code on your production websites:

```html
<script>
(function() {
    window.GOGOL_API_URL = 'https://analytics.yourdomain.com/api/collect';
    var script = document.createElement('script');
    script.src = 'https://analytics.yourdomain.com/assets/js/tracker.js';
    script.async = true;
    document.head.appendChild(script);
})();
</script>
```

## Performance Optimization

1. **Enable PHP OPcache** for better performance
2. **Use CDN** for assets (CSS/JS)
3. **Implement caching** for dashboard queries
4. **Set up cron job** for database cleanup:

```bash
# Crontab entry - clean events older than 90 days
0 2 * * * sqlite3 /var/www/data/analytics.db "DELETE FROM events WHERE created_at < datetime('now', '-90 days');"
```

## Monitoring

Set up monitoring for:
- Server uptime
- Database size
- API response times
- Error rates
- Bot traffic patterns