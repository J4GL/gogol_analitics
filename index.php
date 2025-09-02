<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gogol Analytics Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Gogol Analytics</h1>
            <div class="timeframe-selector">
                <select id="timeframe">
                    <option value="24h" selected>Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
            </div>
        </header>

        <div class="tabs">
            <button class="tab-button active" data-tab="traffic">Traffic</button>
            <button class="tab-button" data-tab="settings">Settings</button>
        </div>

        <div class="tab-content">
            <!-- Traffic Tab -->
            <div id="traffic" class="tab-panel active">
                <div class="dashboard-grid">
                    <div class="stats-cards">
                        <div class="stat-card">
                            <h3>Total Visitors</h3>
                            <div class="stat-value" id="total-visitors">0</div>
                        </div>
                        <div class="stat-card">
                            <h3>New Visitors</h3>
                            <div class="stat-value" id="new-visitors">0</div>
                        </div>
                        <div class="stat-card">
                            <h3>Returning Visitors</h3>
                            <div class="stat-value" id="returning-visitors">0</div>
                        </div>
                        <div class="stat-card">
                            <h3>Bots</h3>
                            <div class="stat-value" id="bots">0</div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h2>Visitor Trends</h2>
                        <canvas id="visitorChart"></canvas>
                    </div>

                    <div class="live-visitors">
                        <h2>Live Visitors</h2>
                        <div class="table-container">
                            <table id="liveTable">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Page</th>
                                        <th>Event</th>
                                        <th>Type</th>
                                        <th>Country</th>
                                    </tr>
                                </thead>
                                <tbody id="liveTableBody">
                                    <!-- Live data will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="additional-stats">
                        <div class="top-pages">
                            <h3>Top Pages</h3>
                            <ul id="topPagesList"></ul>
                        </div>
                        <div class="top-referrers">
                            <h3>Top Referrers</h3>
                            <ul id="topReferrersList"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings" class="tab-panel">
                <div class="settings-content">
                    <h2>Tracking Script</h2>
                    <p>Add the following script to your website to start tracking visitors:</p>
                    
                    <div class="code-snippet">
                        <pre id="trackingCode">&lt;!-- Gogol Analytics Tracking Code --&gt;
&lt;script&gt;
(function() {
    window.GOGOL_API_URL = '<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>/api/collect.php';
    var script = document.createElement('script');
    script.src = '<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>/assets/js/tracker.js';
    script.async = true;
    document.head.appendChild(script);
})();
&lt;/script&gt;
&lt;!-- End Gogol Analytics --&gt;</pre>
                        <button id="copyCode">Copy Code</button>
                    </div>

                    <div class="tracking-info">
                        <h3>What We Track</h3>
                        <ul>
                            <li>Unique Visitors (based on browser fingerprint)</li>
                            <li>Page Views</li>
                            <li>Visitor Location (country from browser locale)</li>
                            <li>Device Information (OS, Browser, Screen Resolution)</li>
                            <li>Page Load Time</li>
                            <li>Bot Detection</li>
                            <li>Referrer Information</li>
                        </ul>
                    </div>

                    <div class="privacy-info">
                        <h3>Privacy First</h3>
                        <p>Gogol Analytics respects user privacy:</p>
                        <ul>
                            <li>No cookies required (uses localStorage for session management)</li>
                            <li>No personal data collection</li>
                            <li>All data stays on your server</li>
                            <li>GDPR compliant</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tooltip -->
        <div id="tooltip" class="tooltip"></div>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>