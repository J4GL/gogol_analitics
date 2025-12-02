package controllers

import (
	"html/template"
	"net/http"
	"path/filepath"
	"gogol_analytics/models"
    "gogol_analytics/database"
	"math/rand"
	"time"
    "fmt"
)

// Helper to parse templates
func parseTemplates(templates ...string) (*template.Template, error) {
	var paths []string
	for _, t := range templates {
		paths = append(paths, filepath.Join("views", t))
	}
	return template.ParseFiles(paths...)
}

func Traffic(w http.ResponseWriter, r *http.Request) {
	// Mock Data Generation (Temporary)
    rand.Seed(time.Now().UnixNano())
    
    // Default to 24h
    points := 24
    chartData := make([]models.ChartDataPoint, points)
    for i := 0; i < points; i++ {
        chartData[i] = models.ChartDataPoint{
            Label: fmt.Sprintf("%02d:00", i),
            Views: rand.Intn(100) + 50,
            NewVisitors: rand.Intn(50) + 10,
            ReturningVisitors: rand.Intn(30) + 5,
            Bots: rand.Intn(10),
        }
    }

    // Helper for tables
    mockTable := func(keys []string) []models.TableRow {
        var rows []models.TableRow
        total := 0
        for _, k := range keys {
            val := rand.Intn(1000)
            rows = append(rows, models.TableRow{Key: k, Value: val})
            total += val
        }
        // Sort and calc percentage would go here, simpler for now:
        return rows
    }

	data := models.TrafficPageData{
        CurrentPage: "traffic",
        TimeRange: "24h",
        ChartData: chartData,
        CountryStats: mockTable([]string{"USA", "Germany", "France", "India", "Brazil"}),
        DeviceStats: mockTable([]string{"Mobile", "Desktop", "Tablet"}),
        OSStats: mockTable([]string{"Windows", "iOS", "Android", "MacOS", "Linux"}),
        SourceStats: mockTable([]string{"Direct", "Websites", "Search engines"}),
        ReferringSitesStats: mockTable([]string{"reddit.com", "t.co", "facebook.com", "news.ycombinator.com", "linkedin.com"}),
        BrowserStats: mockTable([]string{"Chrome", "Safari", "Firefox", "Edge", "Opera"}),
        ResolutionStats: mockTable([]string{"1920x1080", "1366x768", "375x812", "1440x900"}),
        KeywordStats: mockTable([]string{"analytics", "go web dev", "tailwind charts", "mvc golang"}),
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
        ScriptURL: "<script src=\"http://localhost:8090/static/js/tracker.js\"></script>",
        Websites: websites,
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
