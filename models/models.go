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
	Key   string
	Value int
	Percentage float64 // Optional helper for UI bars
}

// TrafficPageData is the specific data structure passed to the Traffic View
type TrafficPageData struct {
    CurrentPage     string
	TimeRange       string
	ChartData       []ChartDataPoint
	CountryStats    []TableRow
	BrowserStats    []TableRow
	ResolutionStats []TableRow
	SourceStats     []TableRow
	ReferringSitesStats []TableRow
	KeywordStats    []TableRow
	DeviceStats     []TableRow
	OSStats         []TableRow
}

// SettingsPageData is data for the settings page
type SettingsPageData struct {
    CurrentPage string
	Websites []Website
	ScriptURL string
}
