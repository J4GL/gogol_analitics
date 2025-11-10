import crypto from 'crypto';

// Bot detection patterns
const BOT_PATTERNS = [
  'bot', 'crawler', 'spider', 'scraper',
  'googlebot', 'bingbot', 'slurp', 'duckduckbot',
  'facebookexternalhit', 'twitterbot',
  'headless', 'phantom', 'selenium', 'puppeteer', 'playwright'
];

export function isBot(userAgent) {
  if (!userAgent) return false;
  const lowerUA = userAgent.toLowerCase();
  return BOT_PATTERNS.some(pattern => lowerUA.includes(pattern));
}

export function generateVisitorId(ip, userAgent) {
  const combined = `${ip}${userAgent}`;
  return crypto.createHash('sha256').update(combined).digest('hex');
}

export function getClientIp(req) {
  return req.headers['x-forwarded-for']?.split(',')[0].trim() ||
         req.headers['x-real-ip'] ||
         req.socket.remoteAddress ||
         '127.0.0.1';
}

export function validateEvent(event) {
  const errors = [];

  // Validate timestamp
  if (!event.timestamp || typeof event.timestamp !== 'number') {
    errors.push('Invalid timestamp');
  } else {
    const now = Date.now();
    const oneDayMs = 24 * 60 * 60 * 1000;
    if (Math.abs(event.timestamp - now) > oneDayMs) {
      errors.push('Timestamp out of acceptable range');
    }
  }

  // Validate required fields
  if (!event.event_type || typeof event.event_type !== 'string') {
    errors.push('Invalid event_type');
  }

  if (!event.page || typeof event.page !== 'string') {
    errors.push('Invalid page URL');
  }

  // Validate country code (if provided)
  if (event.country && !/^[A-Z]{2}$/.test(event.country)) {
    errors.push('Invalid country code format');
  }

  // Validate resolution format (if provided)
  if (event.resolution && !/^\d+x\d+$/.test(event.resolution)) {
    errors.push('Invalid resolution format');
  }

  // Validate page_load (if provided)
  if (event.page_load !== undefined && typeof event.page_load !== 'number') {
    errors.push('Invalid page_load value');
  }

  return errors;
}
