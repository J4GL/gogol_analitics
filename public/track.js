(function() {
  'use strict';

  // Bot detection patterns
  const BOT_PATTERNS = [
    'bot', 'crawler', 'spider', 'scraper',
    'googlebot', 'bingbot', 'slurp', 'duckduckbot',
    'facebookexternalhit', 'twitterbot',
    'headless', 'phantom', 'selenium', 'puppeteer', 'playwright'
  ];

  function isBot() {
    const ua = navigator.userAgent.toLowerCase();
    return BOT_PATTERNS.some(pattern => ua.includes(pattern));
  }

  function parseUserAgent() {
    const ua = navigator.userAgent;
    
    // Detect OS
    let os = 'Unknown';
    if (ua.indexOf('Win') > -1) os = 'Windows';
    else if (ua.indexOf('Mac') > -1) os = 'macOS';
    else if (ua.indexOf('Linux') > -1) os = 'Linux';
    else if (ua.indexOf('Android') > -1) os = 'Android';
    else if (ua.indexOf('iOS') > -1 || ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) os = 'iOS';

    // Detect Browser
    let browser = 'Unknown';
    if (ua.indexOf('Firefox') > -1) browser = 'Firefox';
    else if (ua.indexOf('Chrome') > -1) browser = 'Chrome';
    else if (ua.indexOf('Safari') > -1) browser = 'Safari';
    else if (ua.indexOf('Edge') > -1) browser = 'Edge';
    else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) browser = 'Opera';

    // Add version
    const versionMatch = ua.match(new RegExp(browser + '\\/(\\d+\\.\\d+)'));
    if (versionMatch) {
      browser += ' ' + versionMatch[1];
    }

    // Detect device type
    const deviceType = /Mobile|Android|iPhone|iPad|iPod/.test(ua) ? 'MOBILE' : 'PC';

    return { os, browser, deviceType };
  }

  function getCountryCode() {
    try {
      // Try to get from language
      const lang = navigator.language || navigator.userLanguage;
      if (lang && lang.length >= 2) {
        // Extract country code if format is like 'en-US'
        if (lang.indexOf('-') > -1) {
          return lang.split('-')[1].toUpperCase();
        }
        // Or return the language code uppercase
        return lang.substring(0, 2).toUpperCase();
      }
    } catch (e) {
      // Fallback
    }
    return '';
  }

  function getTimezone() {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (e) {
      return '';
    }
  }

  function getPageLoadTime() {
    try {
      if (window.performance && window.performance.timing) {
        const perfData = window.performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        return pageLoadTime > 0 ? pageLoadTime : null;
      }
    } catch (e) {
      // Performance API not available
    }
    return null;
  }

  function captureEvent(eventType, config) {
    const { os, browser, deviceType } = parseUserAgent();

    const eventData = {
      timestamp: Date.now(),
      event_type: eventType,
      page: window.location.href,
      referrer: document.referrer || '',
      country: getCountryCode(),
      os: os,
      browser: browser,
      device_type: deviceType,
      resolution: `${screen.width}x${screen.height}`,
      timezone: getTimezone(),
      page_load: eventType === 'pageview' ? getPageLoadTime() : null,
      user_agent: navigator.userAgent
    };

    // Send to server
    sendEvent(eventData, config.endpoint);
  }

  function sendEvent(eventData, endpoint) {
    // Use sendBeacon if available (won't be blocked on page unload)
    if (navigator.sendBeacon) {
      const blob = new Blob([JSON.stringify(eventData)], { type: 'application/json' });
      navigator.sendBeacon(endpoint, blob);
    } else {
      // Fallback to fetch
      fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(eventData),
        keepalive: true
      }).catch(err => {
        console.error('Analytics tracking error:', err);
      });
    }
  }

  // Initialize analytics
  window.initAnalytics = function(config) {
    if (!config || !config.endpoint) {
      console.error('Analytics: endpoint is required');
      return;
    }

    // Track pageview on load
    if (document.readyState === 'complete') {
      captureEvent('pageview', config);
    } else {
      window.addEventListener('load', function() {
        captureEvent('pageview', config);
      });
    }

    // Track link clicks
    document.addEventListener('click', function(e) {
      const target = e.target.closest('a');
      if (target && target.href) {
        captureEvent('link', config);
      }
    }, true);

    // Track button clicks
    document.addEventListener('click', function(e) {
      const target = e.target.closest('button');
      if (target) {
        captureEvent('button', config);
      }
    }, true);

    // Track page unload
    window.addEventListener('beforeunload', function() {
      captureEvent('unload', config);
    });
  };

  // Auto-init if config is in window
  if (window.analyticsConfig) {
    window.initAnalytics(window.analyticsConfig);
  }
})();
