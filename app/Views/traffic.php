<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-tabs {
            display: flex;
            gap: 1rem;
        }
        
        .nav-tab {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #6c757d;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .nav-tab.active {
            background: #007bff;
            color: white;
        }
        
        .nav-tab:hover:not(.active) {
            background: #e9ecef;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            height: calc(100vh - 200px);
        }
        
        .chart-section, .events-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
        }
        
        #chart {
            width: 100%;
            height: 400px;
        }
        
        .events-table {
            height: calc(100% - 3rem);
            overflow-y: auto;
        }
        
        .event-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s ease;
            opacity: 1;
        }
        
        .event-row:hover {
            background: #f8f9fa;
        }
        
        .event-row.fade-out {
            opacity: 0;
            transform: translateX(20px);
        }

        /* Custom tooltip */
        .event-tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 1.4;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.1s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .event-tooltip.show {
            opacity: 1;
        }

        .event-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border: 5px solid transparent;
            border-top-color: #333;
        }

        .event-time {
            font-weight: 600;
            color: #007bff;
            font-size: 0.9rem;
        }
        
        .event-page {
            flex: 1;
            margin: 0 1rem;
            font-size: 0.9rem;
            color: #495057;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .event-type {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
        }

        .event-type.page_view {
            background: #007bff;
        }

        .event-type.page_load {
            background: #28a745;
        }

        .event-type.button_click {
            background: #fd7e14;
        }

        .event-type.link_click {
            background: #6f42c1;
        }

        .event-type.bot {
            background: #ffc107;
            color: #212529;
        }

        .event-details {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .country-flag {
            background: #007bff;
            color: white;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .event-browser {
            background: #f8f9fa;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-size: 0.7rem;
        }
        
        .loading {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
        
        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            pointer-events: none;
            z-index: 1000;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Traffic Analytics</h1>
        <nav class="nav-tabs">
            <a href="/" class="nav-tab <?= $currentTab === 'traffic' ? 'active' : '' ?>">Traffic</a>
            <a href="/settings" class="nav-tab <?= $currentTab === 'settings' ? 'active' : '' ?>">Settings</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="dashboard">
            <div class="chart-section">
                <h2 class="section-title">24-Hour Traffic Overview</h2>
                <div id="chart"></div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #dc3545;"></div>
                        <span>Bots</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #28a745;"></div>
                        <span>Unique Visitors</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #007bff;"></div>
                        <span>Returning Visitors</span>
                    </div>
                </div>
            </div>
            
            <div class="events-section">
                <h2 class="section-title">Live Events <span id="events-status" style="font-size: 0.8rem; color: #28a745;">[REAL DATA]</span></h2>
                <div class="events-table" id="events-table">
                    <div class="loading">Loading real events from database...</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Chart implementation
        let chartData = [];
        let chart, xScale, yScale, svg;
        
        function initChart() {
            const margin = {top: 20, right: 30, bottom: 40, left: 50};
            const width = document.getElementById('chart').clientWidth - margin.left - margin.right;
            const height = 400 - margin.top - margin.bottom;
            
            svg = d3.select('#chart')
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom);
            
            const g = svg.append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);
            
            xScale = d3.scaleBand()
                .range([0, width])
                .padding(0.1);
            
            yScale = d3.scaleLinear()
                .range([height, 0]);
            
            // Add axes
            g.append('g')
                .attr('class', 'x-axis')
                .attr('transform', `translate(0,${height})`);
            
            g.append('g')
                .attr('class', 'y-axis');
        }
        
        function updateChart(data) {
            chartData = data;
            
            const keys = ['bots', 'unique', 'returning'];
            const colors = ['#dc3545', '#28a745', '#007bff'];
            
            const stack = d3.stack().keys(keys);
            const series = stack(data);
            
            xScale.domain(data.map(d => {
                const date = new Date(d.hour);
                return date.getHours().toString();
            }));
            
            yScale.domain([0, d3.max(series, d => d3.max(d, d => d[1]))]);
            
            const g = svg.select('g');
            
            // Update axes
            g.select('.x-axis')
                .call(d3.axisBottom(xScale));
            
            g.select('.y-axis')
                .call(d3.axisLeft(yScale));
            
            // Update bars
            const groups = g.selectAll('.series')
                .data(series);
            
            groups.enter()
                .append('g')
                .attr('class', 'series')
                .attr('fill', (d, i) => colors[i])
                .merge(groups)
                .selectAll('rect')
                .data(d => d)
                .join('rect')
                .attr('x', d => xScale(new Date(d.data.hour).getHours().toString()))
                .attr('y', d => yScale(d[1]))
                .attr('height', d => yScale(d[0]) - yScale(d[1]))
                .attr('width', xScale.bandwidth());
        }
        
        function loadStats() {
            fetch('/api/stats?hours=24')
                .then(response => response.json())
                .then(data => {
                    updateChart(data);
                })
                .catch(error => {
                    console.error('Failed to load stats:', error);
                });
        }
        
        // Live events - using real data from API
        function initLiveEvents() {
            const eventsTable = document.getElementById('events-table');
            eventsTable.innerHTML = '<div class="loading">Loading recent events...</div>';

            // Load initial events
            loadRecentEvents();

            // Refresh events every 5 seconds
            setInterval(loadRecentEvents, 5000);
        }

        function loadRecentEvents() {
            console.log('Loading recent events...');
            const eventsTable = document.getElementById('events-table');

            fetch('/api/events?limit=20', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => {
                    console.log('Events API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(events => {
                    console.log('Events loaded:', events.length, events);

                    if (!Array.isArray(events)) {
                        throw new Error('Invalid response format');
                    }

                    if (events.length === 0) {
                        eventsTable.innerHTML = '<div class="loading">No recent events. Events will appear here as visitors browse your site.</div>';
                        return;
                    }

                    // Clean up existing tooltips before clearing events
                    eventsTable.querySelectorAll('.event-row').forEach(row => {
                        if (row._tooltip) {
                            row._tooltip.remove();
                        }
                    });

                    // Clear existing events
                    eventsTable.innerHTML = '';

                    // Update status indicator
                    const statusEl = document.getElementById('events-status');
                    if (statusEl) {
                        statusEl.textContent = `[LIVE DATA - Updated: ${new Date().toLocaleTimeString()}]`;
                        statusEl.style.color = '#28a745';
                    }

                    // Filter events to only show those less than 7 seconds old
                    const now = Math.floor(Date.now() / 1000); // Current time in seconds
                    const recentEvents = events.filter(event => {
                        const eventAge = now - event.timestamp;
                        return eventAge <= 7; // Only show events 7 seconds old or newer
                    });

                    // Add recent events (newest first, already sorted DESC from API)
                    recentEvents.forEach((event) => {
                        addEventRowWithoutTimer(event);
                    });
                })
                .catch(error => {
                    console.error('Failed to load events:', error);
                    eventsTable.innerHTML = `<div class="loading">Failed to load events: ${error.message}</div>`;
                });
        }
        
        function addEventRowWithoutTimer(event) {
            const eventsTable = document.getElementById('events-table');

            const row = document.createElement('div');
            row.className = 'event-row';

            // Store event ID and timestamp
            row.dataset.eventId = event.id;
            row.dataset.timestamp = event.timestamp;

            // Handle timestamp - convert Unix timestamp to local time
            const timeStr = event.timestamp ?
                new Date(event.timestamp * 1000).toLocaleTimeString() :
                new Date().toLocaleTimeString();

            // Determine event type - show actual event type, mark bots
            let eventTypeClass, eventTypeText;
            if (event.is_bot) {
                eventTypeClass = 'bot';
                eventTypeText = `${event.event_type} (bot)`;
            } else {
                eventTypeClass = event.event_type || 'page_view';
                eventTypeText = event.event_type || 'page_view';
            }

            // Simple display: time, page, event type
            row.innerHTML = `
                <div class="event-time">${timeStr}</div>
                <div class="event-page">${event.page || '/'}</div>
                <div class="event-type ${eventTypeClass}">${eventTypeText}</div>
            `;

            // Add custom tooltip functionality
            const tooltip = document.createElement('div');
            tooltip.className = 'event-tooltip';

            // Build tooltip content with page loading time
            let tooltipContent = `Page: ${event.page || '/'}<br>`;
            tooltipContent += `Time: ${timeStr}<br>`;
            tooltipContent += `Event Type: ${event.event_type || 'page_view'}<br>`;

            // Add page loading time if available
            if (event.page_load_ms !== null && event.page_load_ms !== undefined) {
                tooltipContent += `Page Load: ${event.page_load_ms}ms<br>`;
            }

            tooltipContent += `${event.is_bot ? 'Bot: Yes' : 'Bot: No'}<br>`;
            tooltipContent += `Country: ${event.country || 'Unknown'}<br>`;
            tooltipContent += `Browser: ${event.browser || 'Unknown'}<br>`;
            tooltipContent += `OS: ${event.os || 'Unknown'}<br>`;
            tooltipContent += `Referrer: ${event.referrer || 'Direct'}`;

            tooltip.innerHTML = tooltipContent;
            document.body.appendChild(tooltip);

            // Add hover event listeners for immediate tooltip display
            row.addEventListener('mouseenter', function(e) {
                const rect = row.getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2) + 'px';
                tooltip.style.top = (rect.top - 10) + 'px';
                tooltip.classList.add('show');
            });

            row.addEventListener('mouseleave', function() {
                tooltip.classList.remove('show');
            });

            // Store tooltip reference for cleanup
            row._tooltip = tooltip;

            // Add to top (newest first)
            eventsTable.insertBefore(row, eventsTable.firstChild);
        }

        // Keep the original function for new events that come from other sources
        function addEventRow(event) {
            addEventRowWithoutTimer(event);

            // Auto-remove after 7 seconds with fade out animation
            const rows = eventsTable.querySelectorAll('.event-row');
            const latestRow = rows[0]; // The one we just added

            setTimeout(() => {
                if (latestRow && latestRow.parentNode) {
                    latestRow.classList.add('fade-out');
                    setTimeout(() => {
                        if (latestRow.parentNode) {
                            // Clean up tooltip
                            if (latestRow._tooltip) {
                                latestRow._tooltip.remove();
                            }
                            latestRow.parentNode.removeChild(latestRow);
                        }
                    }, 300); // 300ms for fade out animation
                }
            }, 7000); // 7 seconds display time
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
            loadStats();
            initLiveEvents();
            
            // Refresh stats every 5 minutes
            setInterval(loadStats, 5 * 60 * 1000);
        });
    </script>
</body>
</html>
