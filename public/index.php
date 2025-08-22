<?php
require_once __DIR__ . '/../vendor/autoload.php';

use VisitorTracking\Services\AnalyticsService;

// Get basic dashboard stats for initial load
$analyticsService = new AnalyticsService();
$dashboardStats = $analyticsService->getDashboardStats();
$stats = $dashboardStats['status'] === 'success' ? $dashboardStats['stats'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Tracking Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-chart-line"></i> Analytics</h3>
                </div>
                
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" id="traffic-tab" data-bs-toggle="pill" href="#traffic" role="tab">
                            <i class="fas fa-users"></i> Traffic
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pages-tab" data-bs-toggle="pill" href="#pages" role="tab">
                            <i class="fas fa-file-alt"></i> Pages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-tab" data-bs-toggle="pill" href="#settings" role="tab">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Header -->
                <div class="header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Visitor Tracking Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-btn">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar"></i> Period
                            </button>
                            <ul class="dropdown-menu" id="period-dropdown">
                                <li><a class="dropdown-item" href="#" data-period="daily">Daily</a></li>
                                <li><a class="dropdown-item" href="#" data-period="weekly">Weekly</a></li>
                                <li><a class="dropdown-item" href="#" data-period="monthly">Monthly</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tab content -->
                <div class="tab-content" id="dashboard-tabs">
                    <!-- Traffic Tab -->
                    <div class="tab-pane fade show active" id="traffic" role="tabpanel">
                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Visitors
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-visitors">
                                                    <?php echo number_format($stats['total']['total_visitors'] ?? 0); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    New Visitors
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="new-visitors">
                                                    <?php echo number_format($stats['total']['new_visitors'] ?? 0); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Total Pageviews
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-pageviews">
                                                    <?php echo number_format($stats['events']['pageviews'] ?? 0); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-eye fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Active Now
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-visitors">
                                                    <?php echo number_format($stats['live']['active_visitors'] ?? 0); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-circle fa-2x text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart and Live Activity -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-bar"></i> Visitor Analytics
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="visitorChart" style="height: 400px;"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-eye"></i> Live Visitor Activity
                                        </h5>
                                        <span class="badge bg-success" id="live-count">0 active</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-hover table-sm" id="live-visitors-table">
                                                <thead class="sticky-top bg-light">
                                                    <tr>
                                                        <th>Time</th>
                                                        <th>Type</th>
                                                        <th>Event</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Populated by JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pages Tab -->
                    <div class="tab-pane fade" id="pages" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-file-alt"></i> Top Pages
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="pages-table">
                                                <thead>
                                                    <tr>
                                                        <th>Page</th>
                                                        <th>Views</th>
                                                        <th>Unique Visitors</th>
                                                        <th>Bounce Rate</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Populated by JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-external-link-alt"></i> Top Referrers
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="referrers-list">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-code"></i> Tracking Script
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">
                                            Copy and paste this code into your website's HTML, just before the closing &lt;/head&gt; tag.
                                        </p>
                                        
                                        <div class="mb-3">
                                            <label for="site-url" class="form-label">Your Website URL</label>
                                            <input type="url" class="form-control" id="site-url" placeholder="https://yourwebsite.com">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tracking-code" class="form-label">Tracking Code</label>
                                            <textarea class="form-control" id="tracking-code" rows="8" readonly></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-primary" id="generate-code-btn">
                                                <i class="fas fa-sync-alt"></i> Generate Code
                                            </button>
                                            <button class="btn btn-outline-secondary" id="copy-code-btn">
                                                <i class="fas fa-copy"></i> Copy Code
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle"></i> Installation Guide
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <ol class="installation-steps">
                                            <li>Enter your website URL above</li>
                                            <li>Click "Generate Code" to create your tracking script</li>
                                            <li>Copy the generated code</li>
                                            <li>Paste it into your website's HTML before the &lt;/head&gt; tag</li>
                                            <li>Save and publish your website</li>
                                            <li>Visitors will now be tracked automatically</li>
                                        </ol>
                                        
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-lightbulb"></i>
                                            <strong>Tip:</strong> You can install this code on multiple pages to track visitor behavior across your entire website.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-overlay" id="loading-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>