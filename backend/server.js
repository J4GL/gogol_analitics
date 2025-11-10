import express from 'express';
import cors from 'cors';
import rateLimit from 'express-rate-limit';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import {
  initializeDatabase,
  insertEvent,
  getRecentEvents,
  getAggregatedTraffic
} from './db.js';
import {
  isBot,
  generateVisitorId,
  getClientIp,
  validateEvent
} from './utils.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;

// SSE clients
const sseClients = new Set();

// Middleware
app.use(cors({
  origin: process.env.ALLOWED_ORIGINS || '*',
  credentials: false
}));

app.use(express.json());
app.use(express.static(join(__dirname, '..', 'public')));

// Rate limiter for /api/events
const eventsRateLimiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW) || 60000,
  max: parseInt(process.env.RATE_LIMIT_MAX) || 100,
  message: 'Too many requests from this IP'
});

// Initialize database
initializeDatabase();

// API Routes

// POST /api/events - Receive tracking events
app.post('/api/events', eventsRateLimiter, (req, res) => {
  try {
    const eventData = req.body;
    
    // Validate event data
    const validationErrors = validateEvent(eventData);
    if (validationErrors.length > 0) {
      return res.status(400).json({ 
        error: 'Validation failed', 
        details: validationErrors 
      });
    }

    // Extract client IP and generate visitor_id
    const clientIp = getClientIp(req);
    const userAgent = eventData.user_agent || req.headers['user-agent'] || '';
    const visitorId = generateVisitorId(clientIp, userAgent);
    const isBotFlag = isBot(userAgent);

    // Prepare event for database
    const event = {
      timestamp: eventData.timestamp,
      visitor_id: visitorId,
      event_type: eventData.event_type,
      page: eventData.page,
      referrer: eventData.referrer || '',
      country: eventData.country || '',
      os: eventData.os || '',
      browser: eventData.browser || '',
      device_type: eventData.device_type || '',
      resolution: eventData.resolution || '',
      timezone: eventData.timezone || '',
      page_load: eventData.page_load || null,
      is_bot: isBotFlag ? 1 : 0,
      user_agent: userAgent
    };

    // Insert into database
    const insertedEvent = insertEvent(event);

    // Broadcast to SSE clients
    broadcastToSSE(insertedEvent);

    res.status(201).json({ success: true, event: insertedEvent });
  } catch (error) {
    console.error('Error processing event:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// GET /api/events/stream - SSE endpoint for real-time updates
app.get('/api/events/stream', (req, res) => {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');

  // Add client to set
  sseClients.add(res);

  // Send initial connection message
  res.write('data: {"type":"connected"}\n\n');

  // Remove client on disconnect
  req.on('close', () => {
    sseClients.delete(res);
  });
});

// GET /api/traffic/aggregated - Get aggregated traffic data
app.get('/api/traffic/aggregated', (req, res) => {
  try {
    const bucket = req.query.bucket || 'hour';
    const start = parseInt(req.query.start) || (Date.now() - 7 * 24 * 60 * 60 * 1000);
    const end = parseInt(req.query.end) || Date.now();

    const data = getAggregatedTraffic(bucket, start, end);
    res.json(data);
  } catch (error) {
    console.error('Error fetching aggregated data:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// GET /api/events/recent - Get recent events
app.get('/api/events/recent', (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 50;
    const events = getRecentEvents(limit);
    res.json(events);
  } catch (error) {
    console.error('Error fetching recent events:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// GET /api/script - Get tracking script snippet
app.get('/api/script', (req, res) => {
  const host = req.headers.host || 'localhost:3000';
  const protocol = req.headers['x-forwarded-proto'] || 'http';
  const endpoint = `${protocol}://${host}/api/events`;

  res.setHeader('Content-Type', 'text/plain');
  res.send(`<script src="${protocol}://${host}/track.js"></script>
<script>
  if (window.initAnalytics) {
    window.initAnalytics({ endpoint: '${endpoint}' });
  }
</script>`);
});

// Serve frontend
app.get('*', (req, res) => {
  if (!req.path.startsWith('/api')) {
    res.sendFile(join(__dirname, '..', 'public', 'index.html'));
  }
});

// Broadcast event to all SSE clients
function broadcastToSSE(event) {
  const data = `event: new-event\ndata: ${JSON.stringify(event)}\n\n`;
  sseClients.forEach(client => {
    try {
      client.write(data);
    } catch (error) {
      sseClients.delete(client);
    }
  });
}

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Dashboard: http://localhost:${PORT}`);
  console.log(`API: http://localhost:${PORT}/api`);
});
