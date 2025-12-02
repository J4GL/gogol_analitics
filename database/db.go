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
