package models

import "time"

// Website represents a tracked website
type Website struct {
	ID        string
	Name      string
	URL       string
	CreatedAt time.Time
}

// ChartDataPoint represents a single point in the traffic chart
type ChartDataPoint struct {
	Label             string // Timestamp or Date
	Views             int
	NewVisitors       int
	ReturningVisitors int
	Bots              int
}

// TableRow represents a row in the analytics tables
type TableRow struct {
	Key        string
	Value      int
	Percentage float64 // Optional helper for UI bars
}

// TrafficPageData is the specific data structure passed to the Traffic View
type TrafficPageData struct {
	CurrentPage         string
	TimeRange           string
	ChartData           []ChartDataPoint
	PageStats           []TableRow
	CountryStats        []TableRow
	BrowserStats        []TableRow
	ResolutionStats     []TableRow
	SourceStats         []TableRow
	ReferringSitesStats []TableRow
	KeywordStats        []TableRow
	DeviceStats         []TableRow
	OSStats             []TableRow
	RecentEvents        []Event
}

// SettingsPageData is data for the settings page
type SettingsPageData struct {
	CurrentPage string
	Websites    []Website
	ScriptURL   string
}

// Event represents a single traffic event (page view)
type Event struct {
	ID               int64     `json:"-"`
	WebsiteID        string    `json:"website_id"` // Optional: for multi-site support
	Timestamp        time.Time `json:"timestamp"`
	VisitorID        string    `json:"visitor_id"`
	Country          string    `json:"country"`
	CountryCode      string    `json:"country_code"`
	IPHash           string    `json:"ip_hash"`
	UserAgent        string    `json:"user_agent"`
	ScreenResolution string    `json:"screen_resolution"`
	Referrer         string    `json:"referrer"`
	CurrentURL       string    `json:"current_url"`
	IsBot            bool      `json:"is_bot"`

	// Derived fields (parsed server-side)
	OS      string `json:"os"`
	Browser string `json:"browser"`
	Device  string `json:"device"`
	Keyword string `json:"keyword"` // Extracted from referrer if search engine
}
