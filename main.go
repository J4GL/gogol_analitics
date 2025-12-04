package main

import (
	"fmt"
	"gogol_analytics/controllers"
	"gogol_analytics/database"
	"log"
	"net/http"
)

func main() {
	// Initialize Database
	database.InitDB()

	// Static file server
	fs := http.FileServer(http.Dir("./static"))
	http.Handle("/static/", http.StripPrefix("/static/", fs))

	// Routes
	http.HandleFunc("/", controllers.Traffic)
	http.HandleFunc("/conversions", controllers.Conversions)
	http.HandleFunc("/settings", controllers.Settings)
	http.HandleFunc("/settings/add", controllers.SettingsAdd)
	http.HandleFunc("/settings/delete", controllers.SettingsDelete)
	http.HandleFunc("/api/events", controllers.Events)
	http.HandleFunc("/api/track", controllers.Track)
	http.HandleFunc("/api/track-noscript", controllers.TrackNoscript)

	fmt.Println("Server starting on http://localhost:8091")
	if err := http.ListenAndServe("127.0.0.1:8091", nil); err != nil {
		fmt.Printf("Server failed: %v\n", err)
		log.Fatal(err)
	}
	fmt.Println("Server exited unexpectedly")
}
