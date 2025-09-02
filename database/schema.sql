-- Gogol Analytics SQLite Database Schema

-- Visitors table to track unique visitors
CREATE TABLE visitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_id TEXT UNIQUE NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT NOT NULL,
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_bot BOOLEAN DEFAULT FALSE,
    country TEXT,
    os TEXT,
    browser TEXT,
    device_type TEXT DEFAULT 'PC' CHECK(device_type IN ('PC', 'MOBILE')),
    total_visits INTEGER DEFAULT 1
);

CREATE INDEX idx_visitor_id ON visitors(visitor_id);
CREATE INDEX idx_ip ON visitors(ip_address);
CREATE INDEX idx_last_seen ON visitors(last_seen);

-- Events table to track all visitor interactions
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_id TEXT NOT NULL,
    timestamp INTEGER NOT NULL,
    event_type TEXT DEFAULT 'pageview',
    page TEXT NOT NULL,
    referrer TEXT,
    country TEXT,
    os TEXT,
    browser TEXT,
    device_type TEXT DEFAULT 'PC' CHECK(device_type IN ('PC', 'MOBILE')),
    resolution TEXT,
    timezone TEXT,
    page_load_time INTEGER,
    is_bot BOOLEAN DEFAULT FALSE,
    user_agent TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(visitor_id) ON DELETE CASCADE
);

CREATE INDEX idx_events_visitor_id ON events(visitor_id);
CREATE INDEX idx_events_timestamp ON events(timestamp);
CREATE INDEX idx_events_page ON events(page);
CREATE INDEX idx_events_created_at ON events(created_at);

-- Sessions table for tracking visitor sessions
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_id TEXT NOT NULL,
    session_id TEXT NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    page_views INTEGER DEFAULT 1,
    duration INTEGER DEFAULT 0,
    UNIQUE(visitor_id, session_id),
    FOREIGN KEY (visitor_id) REFERENCES visitors(visitor_id) ON DELETE CASCADE
);

CREATE INDEX idx_sessions_visitor_id ON sessions(visitor_id);
CREATE INDEX idx_sessions_session_id ON sessions(session_id);
CREATE INDEX idx_sessions_start_time ON sessions(start_time);