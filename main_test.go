package main

import (
	"context"
	"gogol_analytics/controllers"
	"gogol_analytics/database"
	"gogol_analytics/models"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"

	"github.com/chromedp/chromedp"
)

var (
	testServer     *httptest.Server
	trackingServer *httptest.Server
)

func setup() {
	// Initialize DB (in-memory for tests if possible, or temp file)
	// For this test, we rely on the controllers' in-memory 'events' slice mostly,
	// but the app uses sqlite. Let's use a temp db.
	tmpDB, _ := os.CreateTemp("", "gogol_test_*.db")
	tmpDB.Close()
	os.Setenv("DB_PATH", tmpDB.Name())
	database.InitDB()

	// Reset events
	controllers.ResetEvents()

	// Setup Main App Server
	mux := http.NewServeMux()
	mux.HandleFunc("/api/track", controllers.HandleTrack)
	mux.HandleFunc("/api/pixel", controllers.HandlePixel)
	mux.Handle("/static/", http.StripPrefix("/static/", http.FileServer(http.Dir("static"))))

	// We need to run on port 8091 because tracker.js hardcodes it.
	// So we can't use httptest.NewServer with a random port easily without modifying tracker.js.
	// However, for this test, we can start a real server on 8091 in a goroutine?
	// Or we can modify tracker.js to use relative paths?
	// The user request implies testing the existing setup.
	// Let's try to start a server on 8091. If it fails (port in use), the test might fail.
	// But wait, the user is running the app on 8091.
	// If I run 'go test', it might conflict.
	// I should probably kill the running app first?
	// Or I can make the test server listen on 8091.

	// Actually, let's try to start it on 8091.
	go func() {
		http.ListenAndServe(":8091", mux)
	}()

	// Setup Test Website Server (serving ./test_tracking)
	fileServer := http.FileServer(http.Dir("./test_tracking"))
	trackingServer = httptest.NewServer(fileServer)
}

func teardown() {
	if trackingServer != nil {
		trackingServer.Close()
	}
	// Can't easily stop http.ListenAndServe
}

// Helper to get last event
func getLastEvent() models.TrackEvent {
	events := controllers.GetEvents()
	if len(events) == 0 {
		return models.TrackEvent{}
	}
	return events[len(events)-1]
}

func TestTracking_NormalBrowser(t *testing.T) {
	setup()
	// Give server time to start
	time.Sleep(100 * time.Millisecond)

	// Disable automation flags to simulate real user
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.Flag("enable-automation", false),
		chromedp.Flag("disable-blink-features", "AutomationControlled"),
		chromedp.Flag("headless", true), // Ensure headless is explicit if we want it
	)
	allocCtx, cancelAlloc := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancelAlloc()

	ctx, cancel := chromedp.NewContext(allocCtx)
	defer cancel()

	// Visit Index
	err := chromedp.Run(ctx,
		chromedp.Navigate(trackingServer.URL+"/index.html"),
		chromedp.Sleep(1*time.Second), // Wait for JS to execute
	)
	if err != nil {
		t.Fatalf("Failed to navigate: %v", err)
	}

	// Verify Event
	event := getLastEvent()
	t.Logf("Received Event: %+v", event) // Log event for debugging

	if event.CurrentURL == "" {
		t.Fatal("No event recorded")
	}
	if event.IsBot {
		t.Error("Expected IsBot to be false for normal browser")
	}
	if event.ScreenResolution == "" || event.ScreenResolution == "unknown" {
		t.Error("Expected ScreenResolution to be set")
	}
	if event.Country == "" { // Might be unknown if IP API fails or local IP
		// t.Log("Country might be empty for localhost")
	}

	// Visit Contact
	err = chromedp.Run(ctx,
		chromedp.Navigate(trackingServer.URL+"/contact.html"),
		chromedp.Sleep(1*time.Second),
	)
	if err != nil {
		t.Fatalf("Failed to navigate: %v", err)
	}

	event = getLastEvent()
	if event.CurrentURL == "" {
		t.Fatal("No event recorded for contact page")
	}
}

func TestTracking_NoJS(t *testing.T) {
	// setup() // Already running from previous test?
	// Ideally tests should be independent.
	// Since we use a global port 8091, we can't easily restart it.
	// We'll just reuse it and check the *latest* event.

	// Disable JS
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.Flag("headless", true),
		chromedp.Flag("blink-settings", "imagesEnabled=false"), // Optional
		chromedp.DisableGPU,
	)
	allocCtx, cancelAlloc := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancelAlloc()

	ctx, cancel := chromedp.NewContext(allocCtx)
	defer cancel()
	_ = ctx // Keep ctx alive or just use it?
	// Actually we don't use this ctx, we create a new one below.
	// But we need to keep the browser allocated?
	// The allocCtx handles the browser process.
	// Let's just remove this unused ctx creation if we don't use it.

	// We need to explicitly disable JS via CDP or just use a context that supports it?
	// Chromedp doesn't have a simple "DisableJS" flag in allocator options easily exposed?
	// Actually it does: blink-settings=scriptEnabled=false

	// Let's create a new context with JS disabled
	optsNoJS := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.Flag("blink-settings", "scriptEnabled=false"),
	)
	allocCtxNoJS, cancelAllocNoJS := chromedp.NewExecAllocator(context.Background(), optsNoJS...)
	defer cancelAllocNoJS()

	ctxNoJS, cancelNoJS := chromedp.NewContext(allocCtxNoJS)
	defer cancelNoJS()

	// Visit About
	err := chromedp.Run(ctxNoJS,
		chromedp.Navigate(trackingServer.URL+"/about.html"),
		chromedp.Sleep(1*time.Second),
	)
	if err != nil {
		t.Fatalf("Failed to navigate: %v", err)
	}

	// Verify Event
	event := getLastEvent()
	if event.IsBot != true {
		t.Error("Expected IsBot to be true (fallback for noscript/pixel is usually treated as bot or just limited data)")
	}
	// In HandlePixel: IsBot: true, ScreenResolution: "unknown"
	if event.ScreenResolution != "unknown" {
		t.Errorf("Expected ScreenResolution to be 'unknown', got %s", event.ScreenResolution)
	}
}

func TestTracking_Bot(t *testing.T) {
	// Enable WebDriver to simulate bot
	optsBot := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.Flag("enable-automation", true), // This usually sets navigator.webdriver = true
	)
	allocCtxBot, cancelAllocBot := chromedp.NewExecAllocator(context.Background(), optsBot...)
	defer cancelAllocBot()

	ctxBot, cancelBot := chromedp.NewContext(allocCtxBot)
	defer cancelBot()

	// Visit Index
	err := chromedp.Run(ctxBot,
		chromedp.Navigate(trackingServer.URL+"/index.html"),
		chromedp.Sleep(1*time.Second),
	)
	if err != nil {
		t.Fatalf("Failed to navigate: %v", err)
	}

	// Verify Event
	event := getLastEvent()
	if !event.IsBot {
		t.Error("Expected IsBot to be true when webdriver is enabled")
	}
}
