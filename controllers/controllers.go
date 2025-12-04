package controllers

import (
	"html/template"
	"net/http"
	"path/filepath"
	"gogol_analytics/models"
    "gogol_analytics/database"
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
    // Default to 24h, zero data for now
    points := 24
    chartData := make([]models.ChartDataPoint, points)
    for i := 0; i < points; i++ {
        chartData[i] = models.ChartDataPoint{
            Label: fmt.Sprintf("%02d:00", i),
            Views: 0,
            NewVisitors: 0,
            ReturningVisitors: 0,
            Bots: 0,
        }
    }

	data := models.TrafficPageData{
        CurrentPage: "traffic",
        TimeRange: "24h",
        ChartData: chartData,
        PageStats: []models.TableRow{},
        CountryStats: []models.TableRow{},
        DeviceStats: []models.TableRow{},
        OSStats: []models.TableRow{},
        SourceStats: []models.TableRow{},
        ReferringSitesStats: []models.TableRow{},
        BrowserStats: []models.TableRow{},
        ResolutionStats: []models.TableRow{},
        KeywordStats: []models.TableRow{},
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
        ScriptURL: "<script src=\"http://localhost:8091/static/js/tracker.js\"></script>",
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
