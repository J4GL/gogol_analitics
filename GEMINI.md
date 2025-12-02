**IMPORTANT: Before doing anyting YOU MUST read all this file and reflect on it**

- **CRITICAL - EXECUTE FIRST**: You MUST ALWAYS start your response by using with the appropriate persona (directly related to the task) then fix any typos/errors in the user's request, reflecting on it, and improving it with codebase context. You MUST then explicitly display the line: "I am a <persona> and my task is: <task>" where <task> is the fully refined and contextualized task description. This is MANDATORY for EVERY interaction.
- **CRITICAL - EXECUTE SECOND**: YOU MUST ALWAYS plan you task before implementing it.
You MUST then explicitly display the line: "My plan is: <plan>" where <plan> is the plan you have made. This is MANDATORY for EVERY interaction. Then follow the plan while respecting the following instructions:

- YOU MUST ALWAYS use inline confirmaton instead of javascript alert
- YOU MUST ALWAYS use toast notification instead of javascript alert
- YOU MUST ALWAYS use short timenout for end to end test <= 10 seconds
- YOU MUST NEVER use port 5000
- YOU MUST create and maintain a changelog.md file using Semantic Versioning and Keep a Changelog
- YOU MUST maintain the Project Structure in claude.md file up to date
- YOU MUST always include a working numerical example in comments before implementing any
  monetary calculation: "Example: 100 tokens × $0.000000005/token = $0.0000005"
- YOU MUST ALWAYS re-use existing code before writing new utilities, re-use as much code/ui elements as possible,in css always set variables once and use them everywhere.
- YOU MUST ALWAYS start server in background never in foreground. This is MANDATORY.
- When fixing a bug, you SHOULD NOT rush to the first conclusion, explore other possibilities, reflect on it, and then act.
- Always commit changes immediately after implementing them with a detailed commit message
- Update `GEMINI.md` concisely after commits if the changes affect the project structure or features.
- **CRITICAL - EXECUTE LAST**: And the most importantly: YOU MUST ALWAYS test the feature/functionality you added/changed.This is MANDATORY

Original prompt for this project:
```
I want to make a simple analitics website with 3 page (tab) Traffic,Convertions & Setting. make it in MVC with go as backend and html + tailwinds css and no js framework add meaningfull classname for each part of the page use golang as backend leave the Convertions page empty for now in the settings show: - the js code to paste with only a link to the js file to load - the form to add new website the Traffic page should show: a chart with stacked line for (in this order top to bottom) view, new visitor (uniq), returning visitor (uniq), and bot (uniq) - last 24h by default, last 7 and 30 days in option and bellow tables (most to least): country source user agent screen resolution source (direct , reffer ulr) keywords (extracted from url referer if its a search engine) device (pc,tab,mobile) os (windows,macos,linux,android & ios)
```

# Gogol Analytics

**Gogol Analytics** is a simple, privacy-focused web analytics dashboard built with Go. It provides traffic insights, conversion tracking (placeholder), and website management settings. The project adheres to a classic MVC (Model-View-Controller) architecture without heavy client-side frameworks, utilizing server-side rendered HTML templates styled with Tailwind CSS.

## Project Overview

*   **Backend:** Go (Golang) standard library (`net/http`, `html/template`).
*   **Frontend:** HTML5, Tailwind CSS (via CDN), Chart.js (via CDN).
*   **Architecture:** MVC.
*   **Data Storage:** Currently uses in-memory storage for website settings and mock data for analytics.

## Features

*   **Traffic Analytics:**
    *   Traffic Overview Chart (Views, New/Returning Visitors, Bots).
    *   Top Sources Table (Direct, Websites, Search Engines).
    *   Detailed Tables: Top Countries, User Agents, Screen Resolutions, Top Referring Websites, Keywords, Device Breakdown, OS.
*   **Settings:** Website management (Add/Delete) and tracker script integration.

## Key Directories & Files

*   `main.go`: Application entry point. Configures the HTTP server, routes, and static file serving.
*   `controllers/`: Contains the request handlers (`Traffic`, `Conversions`, `Settings`, etc.) that process logic and render templates.
*   `models/`: Defines the data structures (`Website`, `TrafficPageData`, etc.) used throughout the application.
*   `views/`: HTML templates.
    *   `layout.html`: The base template containing the sidebar, navigation, and common `<head>` elements.
    *   `traffic.html`, `conversions.html`, `settings.html`: Content templates injected into the layout.
*   `static/`: Directory for static assets like CSS and JS files (e.g., `tracker.js`).
*   `go.mod`: Go module definition.

## Building and Running

The application listens on port **8091**.

### Prerequisites
*   Go 1.25+ installed.

### Build and Run
To build the binary and start the server:

```bash
go build -o app && ./app
```

To run in the background (e.g., for development convenience):

```bash
go build -o app && ./app > server.log 2>&1 &
```

Access the dashboard at: **http://localhost:8090**

## Development Conventions

*   **Templates:** The project uses Go's `html/template`.
    *   The `layout.html` defines the outer shell using `{{define "layout"}}`.
    *   Page templates (`traffic.html`, etc.) define their main content in `{{define "content"}}`.
    *   Controllers use a helper `parseTemplates` to combine the layout and the specific page view.
*   **Styling:** Tailwind CSS is loaded via CDN. Configuration is embedded in the `<head>` of `layout.html` to support dark mode and custom colors.
*   **State Management:** Simple state (like the list of tracked websites) is currently managed in-memory within `controllers.go` protected by a mutex (`storeMutex`).
*   **Routing:** All routes are defined in `main.go` using the standard `http.HandleFunc`.
