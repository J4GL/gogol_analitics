package database

import (
	"database/sql"
	"log"
	"time"
    "fmt"
	"gogol_analytics/models"

	_ "github.com/mattn/go-sqlite3"
)

var DB *sql.DB

func InitDB() {
	var err error
	DB, err = sql.Open("sqlite3", "./gogol.db")
	if err != nil {
		log.Fatal(err)
	}

	createTableSQL := `CREATE TABLE IF NOT EXISTS websites (
		"id" TEXT NOT NULL PRIMARY KEY,
		"name" TEXT,
		"url" TEXT,
		"created_at" DATETIME
	);`

	statement, err := DB.Prepare(createTableSQL)
	if err != nil {
		log.Fatal(err)
	}
	statement.Exec()

	createEventsTableSQL := `CREATE TABLE IF NOT EXISTS events (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		website_id TEXT,
		timestamp DATETIME,
		visitor_id TEXT,
		country TEXT,
		country_code TEXT,
		ip TEXT,
		user_agent TEXT,
		screen_resolution TEXT,
		referrer TEXT,
		current_url TEXT,
		is_bot BOOLEAN,
		os TEXT,
		browser TEXT,
		device TEXT,
		keyword TEXT
	);`

	stmtEvents, err := DB.Prepare(createEventsTableSQL)
	if err != nil {
		log.Fatal(err)
	}
	stmtEvents.Exec()
}

func InsertEvent(e models.Event) error {
	stmt, err := DB.Prepare(`INSERT INTO events (
		website_id, timestamp, visitor_id, country, country_code, ip, user_agent, 
		screen_resolution, referrer, current_url, is_bot, os, browser, device, keyword
	) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
	if err != nil {
		return err
	}
	_, err = stmt.Exec(
		e.WebsiteID, e.Timestamp, e.VisitorID, e.Country, e.CountryCode, e.IP, e.UserAgent,
		e.ScreenResolution, e.Referrer, e.CurrentURL, e.IsBot, e.OS, e.Browser, e.Device, e.Keyword,
	)
	return err
}

// GetChartData retrieves traffic data for the chart based on the time range
func GetChartData(timeRange string) ([]models.ChartDataPoint, error) {
	var points int

    // Determine parameters based on range
	switch timeRange {
	case "7d":
		points = 7
	case "30d":
		points = 30
	default: // 24h
		points = 24
	}

    // We need to generate a list of all time slots first to fill gaps (Left Join approach is complex in simple Go/SQLite)
    // Alternatively, we fetch all data in range and bucket it in Go. This is easier and likely fast enough for this scale.
    
    startLimit := time.Now()
    if timeRange == "24h" {
        startLimit = startLimit.Add(-24 * time.Hour)
    } else if timeRange == "7d" {
        startLimit = startLimit.AddDate(0, 0, -7)
    } else {
        startLimit = startLimit.AddDate(0, 0, -30)
    }

	rows, err := DB.Query(`
		SELECT timestamp, is_bot, visitor_id
		FROM events 
		WHERE timestamp >= ? 
		ORDER BY timestamp ASC
	`, startLimit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

    // Bucket Logic
    buckets := make([]models.ChartDataPoint, points)
    now := time.Now()
    
    // Initialize buckets with labels
    for i := 0; i < points; i++ {
        var label string
        if timeRange == "24h" {
             // Current hour back to -23h
             t := now.Add(time.Duration(-(points - 1 - i)) * time.Hour)
             label = t.Format("15:00")
             buckets[i].Label = label
        } else {
             // Current day back to -N days
             t := now.AddDate(0, 0, -(points - 1 - i))
             label = t.Format("02 Jan") // Or Mon for 7d
             if timeRange == "7d" {
                 label = t.Format("Mon")
             }
             buckets[i].Label = label
        }
    }

    // Process rows
    seenVisitors := make(map[string]bool) // Unique for this query
    
    for rows.Next() {
        var ts time.Time
        var isBot bool
        var vid string
        if err := rows.Scan(&ts, &isBot, &vid); err != nil {
            continue
        }
        
        // Find bucket index
        var index int
        if timeRange == "24h" {
            hoursAgo := int(now.Sub(ts).Hours())
            index = points - 1 - hoursAgo
        } else {
            daysAgo := int(now.Sub(ts).Hours() / 24)
            index = points - 1 - daysAgo
        }
        
        if index >= 0 && index < points {
            buckets[index].Views++
            if isBot {
                buckets[index].Bots++
            } else {
                if !seenVisitors[vid] {
                    buckets[index].NewVisitors++
                    seenVisitors[vid] = true
                } else {
                    buckets[index].ReturningVisitors++
                }
            }
        }
    }

	return buckets, nil
}

// GetTopStatsGeneric aggregates counts for a specific column
func GetTopStats(column string, limit int) ([]models.TableRow, error) {
    // Safelist columns to prevent SQL injection
    allowed := map[string]bool{
        "current_url": true, "country": true, "os": true, "browser": true, 
        "screen_resolution": true, "referrer": true, "keyword": true, "device": true,
    }
    if !allowed[column] {
        return nil, fmt.Errorf("invalid column")
    }

	query := fmt.Sprintf(`
		SELECT %s, COUNT(*) as count 
		FROM events 
		WHERE %s != ''
		GROUP BY %s 
		ORDER BY count DESC 
		LIMIT ?
	`, column, column, column)

	rows, err := DB.Query(query, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var stats []models.TableRow
	for rows.Next() {
		var row models.TableRow
		if err := rows.Scan(&row.Key, &row.Value); err != nil {
			continue
		}
		stats = append(stats, row)
	}
	return stats, nil
}

// GetRecentEvents retrieves the latest N events
func GetRecentEvents(limit int) ([]models.Event, error) {
	rows, err := DB.Query(`
		SELECT timestamp, country, current_url, referrer, keyword, os, browser, screen_resolution, device, ip, is_bot 
		FROM events 
		ORDER BY timestamp DESC 
		LIMIT ?
	`, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var events []models.Event
	for rows.Next() {
		var e models.Event
        // Handle potentially nullable fields if schema wasn't strict, but here we defined them as text. 
        // SQLite might return null for empty strings if not careful, but our insert logic uses empty strings.
		if err := rows.Scan(
            &e.Timestamp, &e.Country, &e.CurrentURL, &e.Referrer, &e.Keyword, 
            &e.OS, &e.Browser, &e.ScreenResolution, &e.Device, &e.IP, &e.IsBot,
        ); err != nil {
			return nil, err
		}
		events = append(events, e)
	}
	return events, nil
}

func GetWebsites() ([]models.Website, error) {
	rows, err := DB.Query("SELECT id, name, url, created_at FROM websites ORDER BY created_at DESC")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var websites []models.Website
	for rows.Next() {
		var w models.Website
		if err := rows.Scan(&w.ID, &w.Name, &w.URL, &w.CreatedAt); err != nil {
			return nil, err
		}
		websites = append(websites, w)
	}
	return websites, nil
}

func AddWebsite(name, url string) error {
    // Generate a simple ID like before
    id := fmt.Sprintf("SITE_%d", time.Now().Unix())
    
	statement, err := DB.Prepare("INSERT INTO websites (id, name, url, created_at) VALUES (?, ?, ?, ?)")
	if err != nil {
		return err
	}
	_, err = statement.Exec(id, name, url, time.Now())
	return err
}

func DeleteWebsite(id string) error {
	statement, err := DB.Prepare("DELETE FROM websites WHERE id = ?")
	if err != nil {
		return err
	}
	_, err = statement.Exec(id)
	return err
}
