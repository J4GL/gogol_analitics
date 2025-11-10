import Database from 'better-sqlite3';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const dbPath = process.env.DATABASE_PATH || join(__dirname, '..', 'analytics.db');
const db = new Database(dbPath);

// Enable WAL mode for better concurrency
db.pragma('journal_mode = WAL');

// Initialize database schema
export function initializeDatabase() {
  const createTableSQL = `
    CREATE TABLE IF NOT EXISTS events (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      timestamp INTEGER NOT NULL,
      visitor_id TEXT NOT NULL,
      event_type TEXT NOT NULL,
      page TEXT NOT NULL,
      referrer TEXT,
      country TEXT,
      os TEXT,
      browser TEXT,
      device_type TEXT,
      resolution TEXT,
      timezone TEXT,
      page_load INTEGER,
      is_bot INTEGER DEFAULT 0,
      user_agent TEXT
    )
  `;

  db.exec(createTableSQL);

  // Create indexes for performance
  const indexes = [
    'CREATE INDEX IF NOT EXISTS idx_timestamp ON events(timestamp)',
    'CREATE INDEX IF NOT EXISTS idx_visitor_id ON events(visitor_id)',
    'CREATE INDEX IF NOT EXISTS idx_timestamp_visitor ON events(timestamp, visitor_id)',
    'CREATE INDEX IF NOT EXISTS idx_is_bot ON events(is_bot)'
  ];

  indexes.forEach(indexSQL => db.exec(indexSQL));

  console.log('Database initialized successfully');
}

// Insert a new event
export function insertEvent(event) {
  const stmt = db.prepare(`
    INSERT INTO events (
      timestamp, visitor_id, event_type, page, referrer,
      country, os, browser, device_type, resolution,
      timezone, page_load, is_bot, user_agent
    ) VALUES (
      @timestamp, @visitor_id, @event_type, @page, @referrer,
      @country, @os, @browser, @device_type, @resolution,
      @timezone, @page_load, @is_bot, @user_agent
    )
  `);

  const info = stmt.run(event);
  return { id: info.lastInsertRowid, ...event };
}

// Get recent events (for live table)
export function getRecentEvents(limit = 50) {
  const stmt = db.prepare(`
    SELECT * FROM events
    ORDER BY timestamp DESC
    LIMIT ?
  `);

  return stmt.all(limit);
}

// Check if visitor is new (first event for this visitor_id)
export function isNewVisitor(visitorId, beforeTimestamp) {
  const stmt = db.prepare(`
    SELECT COUNT(*) as count FROM events
    WHERE visitor_id = ? AND timestamp < ?
  `);

  const result = stmt.get(visitorId, beforeTimestamp);
  return result.count === 0;
}

// Get aggregated traffic data
export function getAggregatedTraffic(bucket, start, end) {
  let bucketSize;
  
  switch (bucket) {
    case 'hour':
      bucketSize = 60 * 60 * 1000; // 1 hour in milliseconds
      break;
    case 'day':
      bucketSize = 24 * 60 * 60 * 1000; // 1 day
      break;
    case 'week':
      bucketSize = 7 * 24 * 60 * 60 * 1000; // 1 week
      break;
    default:
      bucketSize = 60 * 60 * 1000; // default to hour
  }

  // Get all events in the time range
  const stmt = db.prepare(`
    SELECT * FROM events
    WHERE timestamp >= ? AND timestamp <= ?
    ORDER BY timestamp ASC
  `);

  const events = stmt.all(start, end);

  // Aggregate into buckets
  const buckets = new Map();
  const visitorFirstSeen = new Map(); // Track first appearance of each visitor

  events.forEach(event => {
    const bucketStart = Math.floor(event.timestamp / bucketSize) * bucketSize;
    
    if (!buckets.has(bucketStart)) {
      buckets.set(bucketStart, {
        bucket_start: bucketStart,
        bots: new Set(),
        new_visitors: new Set(),
        returning_visitors: new Set()
      });
    }

    const bucket = buckets.get(bucketStart);

    if (event.is_bot) {
      bucket.bots.add(event.visitor_id);
    } else {
      // Check if this is the first time we've seen this visitor
      if (!visitorFirstSeen.has(event.visitor_id)) {
        visitorFirstSeen.set(event.visitor_id, event.timestamp);
        bucket.new_visitors.add(event.visitor_id);
      } else {
        // Returning visitor
        bucket.returning_visitors.add(event.visitor_id);
      }
    }
  });

  // Convert Sets to counts
  return Array.from(buckets.values()).map(bucket => ({
    bucket_start: bucket.bucket_start,
    bots: bucket.bots.size,
    new_visitors: bucket.new_visitors.size,
    returning_visitors: bucket.returning_visitors.size
  }));
}

export default db;
