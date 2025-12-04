package controllers

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"gogol_analytics/database"
	"gogol_analytics/models"
	"html/template"
	"net/http"
	"net/url"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

// --- SSE Broker ---

type Broker struct {
	clients map[chan models.Event]bool
	mutex   sync.Mutex
}

var sseBroker = &Broker{
	clients: make(map[chan models.Event]bool),
}

func (b *Broker) AddClient() chan models.Event {
	b.mutex.Lock()
	defer b.mutex.Unlock()
	ch := make(chan models.Event, 10) // Buffer to prevent blocking
	b.clients[ch] = true
	return ch
}

func (b *Broker) RemoveClient(ch chan models.Event) {
	b.mutex.Lock()
	defer b.mutex.Unlock()
	delete(b.clients, ch)
	close(ch)
}

func (b *Broker) Broadcast(event models.Event) {
	b.mutex.Lock()
	defer b.mutex.Unlock()
	for ch := range b.clients {
		select {
		case ch <- event:
		default:
			// Skip if client buffer is full (slow client)
		}
	}
}

// --- Helpers ---

func parseTemplates(templates ...string) (*template.Template, error) {
	var paths []string
	for _, t := range templates {
		paths = append(paths, filepath.Join("views", t))
	}
	return template.ParseFiles(paths...)
}

func parseUA(ua string) (os, browser, device string) {
	// Simple heuristics
	ua = strings.ToLower(ua)

	// OS
	if strings.Contains(ua, "windows") {
		os = "Windows"
	} else if strings.Contains(ua, "macintosh") {
		os = "macOS"
	} else if strings.Contains(ua, "android") {
		os = "Android"
	} else if strings.Contains(ua, "iphone") || strings.Contains(ua, "ipad") {
		os = "iOS"
	} else if strings.Contains(ua, "linux") {
		os = "Linux"
	} else {
		os = "Unknown"
	}

	// Browser
	if strings.Contains(ua, "edg/") {
		browser = "Edge"
	} else if strings.Contains(ua, "chrome/") {
		browser = "Chrome"
	} else if strings.Contains(ua, "firefox/") {
		browser = "Firefox"
	} else if strings.Contains(ua, "safari/") {
		browser = "Safari"
	} else {
		browser = "Other"
	}

	// Device
	if strings.Contains(ua, "mobile") || strings.Contains(ua, "android") || strings.Contains(ua, "iphone") {
		device = "Mobile"
	} else if strings.Contains(ua, "ipad") || strings.Contains(ua, "tablet") {
		device = "Tablet"
	} else {
		device = "Desktop"
	}

	return
}

func parseKeyword(referrer string) string {
	if referrer == "" {
		return ""
	}
	u, err := url.Parse(referrer)
	if err != nil {
		return ""
	}
	// Check common query params
	q := u.Query().Get("q")
	if q != "" {
		return q
	}
	p := u.Query().Get("p") // Yahoo
	if p != "" {
		return p
	}
	return ""
}

func extractPath(urlStr string) string {
	if urlStr == "" {
		return "/"
	}
	u, err := url.Parse(urlStr)
	if err != nil {
		return urlStr // Return original if can't parse
	}
	path := u.Path
	if path == "" {
		return "/"
	}
	return path
}

func extractTLD(urlStr string) string {
	if urlStr == "" || urlStr == "Direct" {
		return urlStr
	}
	u, err := url.Parse(urlStr)
	if err != nil {
		return urlStr // Return original if can't parse
	}
	host := u.Host
	if host == "" {
		host = u.Path // Sometimes URLs are just domains
	}
	// Remove port if present
	if idx := strings.Index(host, ":"); idx != -1 {
		host = host[:idx]
	}
	return host
}

// --- Handlers ---

func Track(w http.ResponseWriter, r *http.Request) {
	// CORS headers
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type")

	if r.Method == "OPTIONS" {
		w.WriteHeader(http.StatusOK)
		return
	}

	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Temporary struct to receive IP from client
	var payload struct {
		models.Event
		IP string `json:"ip"`
	}

	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		http.Error(w, "Bad request", http.StatusBadRequest)
		return
	}

	event := payload.Event

	// Fill missing server-side fields
	event.Timestamp = time.Now()
	event.OS, event.Browser, event.Device = parseUA(event.UserAgent)
	event.Keyword = parseKeyword(event.Referrer)

	// Hash IP with UserAgent as salt for privacy
	// This creates a unique identifier while not storing the actual IP
	hash := sha256.Sum256([]byte(payload.IP + event.UserAgent))
	event.IPHash = hex.EncodeToString(hash[:])

	// Use the same hash for VisitorID (IP + UserAgent uniquely identifies a visitor)
	event.VisitorID = event.IPHash

	// Save to DB
	if err := database.InsertEvent(event); err != nil {
		fmt.Printf("DB Error: %v\n", err)
		// Don't fail the request, just log
	}

	// Broadcast to SSE
	sseBroker.Broadcast(event)

	w.WriteHeader(http.StatusNoContent)
}

func Traffic(w http.ResponseWriter, r *http.Request) {
	timeRange := r.URL.Query().Get("range")
	if timeRange == "" {
		timeRange = "24h"
	}

	chartData, err := database.GetChartData(timeRange)
	if err != nil {
		fmt.Printf("Error getting chart data: %v\n", err)
	}

	// Helper to get stats safely
	getStats := func(col string) []models.TableRow {
		s, err := database.GetTopStats(col, 10)
		if err != nil {
			fmt.Printf("Error getting stats for %s: %v\n", col, err)
			return []models.TableRow{}
		}
		return s
	}

	// Get recent events
	recentEvents, err := database.GetRecentEvents(20)
	if err != nil {
		fmt.Printf("Error getting recent events: %v\n", err)
	}

	// Get source stats specifically to handle "Direct"
	sourceStats, err := database.GetTopSources(10)
	if err != nil {
		fmt.Printf("Error getting source stats: %v\n", err)
	}

	// Process page stats to show only path
	pageStats := getStats("current_url")
	for i := range pageStats {
		pageStats[i].Key = extractPath(pageStats[i].Key)
	}

	// Process source stats to show only TLD
	for i := range sourceStats {
		sourceStats[i].Key = extractTLD(sourceStats[i].Key)
	}

	// Process referring sites to show only TLD
	referringSitesStats := getStats("referrer")
	for i := range referringSitesStats {
		referringSitesStats[i].Key = extractTLD(referringSitesStats[i].Key)
	}

	data := models.TrafficPageData{
		CurrentPage:         "traffic",
		TimeRange:           timeRange,
		ChartData:           chartData,
		PageStats:           pageStats,
		CountryStats:        getStats("country"),
		DeviceStats:         getStats("device"),
		OSStats:             getStats("os"),
		SourceStats:         sourceStats,
		ReferringSitesStats: referringSitesStats,
		BrowserStats:        getStats("browser"),
		ResolutionStats:     getStats("screen_resolution"),
		KeywordStats:        getStats("keyword"),
		RecentEvents:        recentEvents,
	}

	tmpl, err := parseTemplates("layout.html", "traffic.html")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	tmpl.ExecuteTemplate(w, "layout", data)
}

func Conversions(w http.ResponseWriter, r *http.Request) {
	tmpl, err := parseTemplates("layout.html", "conversions.html")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	// Anonymous struct for now
	data := struct{ CurrentPage string }{CurrentPage: "conversions"}
	tmpl.ExecuteTemplate(w, "layout", data)
}

func Settings(w http.ResponseWriter, r *http.Request) {
	websites, err := database.GetWebsites()
	if err != nil {
		http.Error(w, "Database error", http.StatusInternalServerError)
		return
	}

	data := models.SettingsPageData{
		CurrentPage: "settings",
		ScriptURL:   "<script src=\"http://localhost:8091/static/js/tracker.js\"></script>",
		Websites:    websites,
	}

	tmpl, err := parseTemplates("layout.html", "settings.html")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	tmpl.ExecuteTemplate(w, "layout", data)
}

func SettingsAdd(w http.ResponseWriter, r *http.Request) {
	if r.Method == "POST" {
		if err := r.ParseForm(); err != nil {
			http.Error(w, "Invalid request", http.StatusBadRequest)
			return
		}

		name := r.FormValue("website_name")
		url := r.FormValue("website_url")

		if name != "" && url != "" {
			if err := database.AddWebsite(name, url); err != nil {
				http.Error(w, "Database error", http.StatusInternalServerError)
				return
			}
		}

		http.Redirect(w, r, "/settings", http.StatusSeeOther)
		return
	}
	http.Redirect(w, r, "/settings", http.StatusSeeOther)
}

func SettingsDelete(w http.ResponseWriter, r *http.Request) {
	if r.Method == "POST" { // Prefer POST for state changing actions
		if err := r.ParseForm(); err != nil {
			http.Error(w, "Invalid request", http.StatusBadRequest)
			return
		}

		id := r.FormValue("id")
		if err := database.DeleteWebsite(id); err != nil {
			http.Error(w, "Database error", http.StatusInternalServerError)
			return
		}
	}
	http.Redirect(w, r, "/settings", http.StatusSeeOther)
}

func Events(w http.ResponseWriter, r *http.Request) {
	// Set headers for SSE
	w.Header().Set("Content-Type", "text/event-stream")
	w.Header().Set("Cache-Control", "no-cache")
	w.Header().Set("Connection", "keep-alive")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	rc := http.NewResponseController(w)

	// Send initial ping
	fmt.Fprintf(w, ": ping\n\n")
	rc.Flush()

	// Register client
	eventChan := sseBroker.AddClient()
	defer sseBroker.RemoveClient(eventChan)

	// Client disconnected?
	clientGone := r.Context().Done()

	// Heartbeat to keep connection open
	ticker := time.NewTicker(15 * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-clientGone:
			return
		case <-ticker.C:
			fmt.Fprintf(w, ": heartbeat\n\n")
			rc.Flush()
		case event := <-eventChan:
			// Marshal event to JSON
			data, err := json.Marshal(event)
			if err != nil {
				continue
			}
			fmt.Fprintf(w, "data: %s\n\n", data)
			if err := rc.Flush(); err != nil {
				return
			}
		}
	}
}
