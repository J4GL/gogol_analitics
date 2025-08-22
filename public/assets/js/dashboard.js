/**
 * Visitor Tracking Dashboard JavaScript
 * Handles all dashboard interactions, data loading, and real-time updates
 */

class VisitorDashboard {
    constructor() {
        this.currentPeriod = 'daily';
        this.currentChart = null;
        this.refreshInterval = null;
        this.liveUpdateInterval = null;
        this.visitorData = new Map(); // Store visitor data with timestamps
        this.visitorExpirationTimeout = 15000; // 15 seconds in milliseconds
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.startLiveUpdates();
        this.generateTrackingCode();
    }
    
    setupEventListeners() {
        // Period selection
        document.querySelectorAll('#period-dropdown a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.changePeriod(e.target.dataset.period);
            });
        });
        
        // Refresh button
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.refreshData();
        });
        
        // Tab switches
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.handleTabSwitch(e.target.getAttribute('href'));
            });
        });
        
        // Settings form
        document.getElementById('generate-code-btn').addEventListener('click', () => {
            this.generateTrackingCode();
        });
        
        document.getElementById('copy-code-btn').addEventListener('click', () => {
            this.copyTrackingCode();
        });
        
        // Site URL input
        document.getElementById('site-url').addEventListener('input', () => {
            this.generateTrackingCode();
        });
    }
    
    async loadInitialData() {
        this.showLoading(true);
        
        try {
            await Promise.all([
                this.loadVisitorChart(),
                this.loadPageAnalytics(),
                this.updateStats(),
                this.loadLiveVisitors() // Load live visitors on initial load since they're now in Traffic tab
            ]);
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showError('Failed to load dashboard data');
        }
        
        this.showLoading(false);
    }
    
    async loadVisitorChart() {
        try {
            const response = await fetch(`/api/analytics.php?format=chart&period=${this.currentPeriod}`);
            const data = await response.json();
            
            if (data.status === 'success') {
                this.renderChart(data.chart_data, data.chart_options);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading visitor chart:', error);
            throw error;
        }
    }
    
    renderChart(chartData, chartOptions) {
        const ctx = document.getElementById('visitorChart').getContext('2d');
        
        if (this.currentChart) {
            this.currentChart.destroy();
        }
        
        this.currentChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                ...chartOptions,
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            footer: function(tooltipItems) {
                                let total = 0;
                                tooltipItems.forEach(item => {
                                    total += item.raw;
                                });
                                return 'Total: ' + total;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        });
    }
    
    async loadPageAnalytics() {
        try {
            const response = await fetch('/api/data.php?action=pages');
            const data = await response.json();
            
            if (data.status === 'success') {
                this.updatePagesTable(data.pages);
                this.updateReferrersList(data.referrers);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading page analytics:', error);
            this.showError('Failed to load page analytics');
        }
    }
    
    updatePagesTable(pages) {
        const tbody = document.querySelector('#pages-table tbody');
        tbody.innerHTML = '';
        
        if (pages.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No page data available</td></tr>';
            return;
        }
        
        pages.forEach(page => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="page-url" title="${page.url}">
                        <strong>${page.title}</strong><br>
                        <small class="text-muted">${page.url}</small>
                    </div>
                </td>
                <td><span class="badge bg-primary">${page.views.toLocaleString()}</span></td>
                <td><span class="badge bg-info">${page.unique_visitors.toLocaleString()}</span></td>
                <td><span class="badge bg-secondary">${page.bounce_rate.toFixed(1)}%</span></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    updateReferrersList(referrers) {
        const container = document.getElementById('referrers-list');
        container.innerHTML = '';
        
        if (referrers.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No referrer data available</p>';
            return;
        }
        
        referrers.forEach(referrer => {
            const item = document.createElement('div');
            item.className = 'list-group-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="referrer-domain">${referrer.domain}</div>
                        <div class="referrer-stats">
                            ${referrer.visits.toLocaleString()} visits • 
                            ${referrer.unique_visitors.toLocaleString()} unique
                        </div>
                    </div>
                    <span class="badge bg-primary">${referrer.visits.toLocaleString()}</span>
                </div>
            `;
            container.appendChild(item);
        });
    }
    
    async updateStats() {
        try {
            const response = await fetch('/api/live-visitors.php?type=stats');
            const data = await response.json();
            
            if (data.status === 'success') {
                const stats = data.stats;
                
                // Update stat cards
                document.getElementById('total-visitors').textContent = 
                    (stats.total.total_visitors || 0).toLocaleString();
                document.getElementById('new-visitors').textContent = 
                    (stats.total.new_visitors || 0).toLocaleString();
                document.getElementById('total-pageviews').textContent = 
                    (stats.events.pageviews || 0).toLocaleString();
                document.getElementById('active-visitors').textContent = 
                    (stats.live.active_visitors || 0).toLocaleString();
            }
        } catch (error) {
            console.error('Error updating stats:', error);
        }
    }
    
    async loadLiveVisitors() {
        try {
            const response = await fetch('/api/live-visitors.php?type=feed&minutes=30');
            const data = await response.json();
            
            if (data.status === 'success') {
                this.updateLiveVisitorsTable(data.live_visitors);
                this.updateLiveCount(data.active_count);
            }
        } catch (error) {
            console.error('Error loading live visitors:', error);
        }
    }
    
    updateLiveVisitorsTable(visitors) {
        const tbody = document.querySelector('#live-visitors-table tbody');
        const now = Date.now();
        
        // Update visitor data with new arrivals
        visitors.forEach(visitor => {
            const key = `${visitor.visitor_uuid}-${visitor.timestamp}`;
            if (!this.visitorData.has(key)) {
                this.visitorData.set(key, {
                    ...visitor,
                    addedAt: now
                });
            }
        });
        
        // Remove expired visitors (older than 15 seconds)
        for (const [key, data] of this.visitorData.entries()) {
            if (now - data.addedAt > this.visitorExpirationTimeout) {
                this.visitorData.delete(key);
            }
        }
        
        // Convert to array and sort by timestamp (newest first)
        const activeVisitors = Array.from(this.visitorData.values())
            .sort((a, b) => b.timestamp - a.timestamp);
        
        tbody.innerHTML = '';
        
        if (activeVisitors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No live visitors</td></tr>';
            return;
        }
        
        activeVisitors.forEach(visitor => {
            const row = document.createElement('tr');
            const visitorType = visitor.visitor_type || 'unknown';
            const badgeClass = this.getVisitorTypeBadgeClass(visitorType);
            
            // Create tooltip content with all visitor information
            const tooltipContent = this.createTooltipContent(visitor);
            
            row.innerHTML = `
                <td>
                    <small class="text-muted">${visitor.time_ago || 'unknown'}</small>
                </td>
                <td>
                    <span class="badge ${badgeClass} visitor-type-badge small">
                        ${visitorType}
                    </span>
                </td>
                <td>
                    <span class="badge bg-light text-dark small event-tooltip" 
                          data-bs-toggle="tooltip" 
                          data-bs-placement="top" 
                          data-bs-html="true" 
                          title="${tooltipContent}">
                        ${visitor.event_type || 'unknown'}
                    </span>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Initialize tooltips for new elements
        this.initializeTooltips();
    }
    
    updateLiveCount(count) {
        const liveCountElement = document.getElementById('live-count');
        liveCountElement.textContent = `${count} active`;
        liveCountElement.className = count > 0 ? 'badge bg-success' : 'badge bg-secondary';
    }
    
    getVisitorTypeBadgeClass(type) {
        switch (type) {
            case 'new':
                return 'bg-success visitor-new';
            case 'returning':
                return 'bg-primary visitor-returning';
            case 'bot':
                return 'bg-danger visitor-bot';
            default:
                return 'bg-secondary';
        }
    }
    
    createTooltipContent(visitor) {
        const pageTitle = visitor.page_title || 'Unknown Page';
        const pageUrl = visitor.page_url || 'Unknown URL';
        const visitorUuid = visitor.visitor_uuid || 'Unknown';
        const eventData = visitor.event_data ? JSON.stringify(visitor.event_data, null, 2) : 'No additional data';
        
        return `
            <div class="text-start">
                <strong>Visitor:</strong> ${visitorUuid}<br>
                <strong>Page:</strong> ${pageTitle}<br>
                <strong>URL:</strong> ${pageUrl}<br>
                <strong>Event:</strong> ${visitor.event_type || 'unknown'}<br>
                <strong>Time:</strong> ${this.formatTime(visitor.timestamp)}<br>
                ${visitor.referrer ? `<strong>Referrer:</strong> ${visitor.referrer}<br>` : ''}
                ${eventData !== 'No additional data' ? `<strong>Data:</strong><br><pre class="small">${eventData}</pre>` : ''}
            </div>
        `.replace(/"/g, '&quot;');
    }
    
    initializeTooltips() {
        // Dispose of existing tooltips to avoid memory leaks
        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        existingTooltips.forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });
        
        // Initialize new tooltips
        const tooltipElements = document.querySelectorAll('.event-tooltip');
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element, {
                container: 'body',
                sanitize: false // Allow HTML content
            });
        });
    }
    
    changePeriod(period) {
        this.currentPeriod = period;
        
        // Update dropdown button text
        const dropdownButton = document.querySelector('#period-dropdown').previousElementSibling;
        dropdownButton.innerHTML = `<i class="fas fa-calendar"></i> ${period.charAt(0).toUpperCase() + period.slice(1)}`;
        
        // Reload chart data
        this.loadVisitorChart();
    }
    
    async refreshData() {
        const refreshBtn = document.getElementById('refresh-btn');
        const originalHTML = refreshBtn.innerHTML;
        
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;
        
        try {
            await this.loadInitialData();
        } finally {
            refreshBtn.innerHTML = originalHTML;
            refreshBtn.disabled = false;
        }
    }
    
    handleTabSwitch(tabId) {
        switch (tabId) {
            case '#traffic':
                this.loadLiveVisitors(); // Refresh live visitors when switching to traffic tab
                break;
            case '#pages':
                this.loadPageAnalytics();
                break;
            case '#settings':
                this.generateTrackingCode();
                break;
        }
    }
    
    startLiveUpdates() {
        // Update live visitors every 5 seconds
        this.liveUpdateInterval = setInterval(() => {
            // Always update live visitors since they're now in the Traffic tab
            this.loadLiveVisitors();
            
            // Always update stats
            this.updateStats();
        }, 5000);
    }
    
    generateTrackingCode() {
        const siteUrl = document.getElementById('site-url').value || window.location.origin;
        const apiUrl = `${window.location.origin}/api/track.php`;
        const trackerUrl = `${window.location.origin}/assets/js/tracker.js`;
        
        const trackingCode = `<!-- Visitor Tracking by Gogol Analytics -->
<script>
  (function() {
    var script = document.createElement('script');
    script.src = '${trackerUrl}';
    script.setAttribute('data-api-url', '${apiUrl}');
    script.setAttribute('data-site-url', '${siteUrl}');
    script.async = true;
    document.head.appendChild(script);
  })();
</script>`;
        
        document.getElementById('tracking-code').value = trackingCode;
    }
    
    copyTrackingCode() {
        const trackingCode = document.getElementById('tracking-code');
        trackingCode.select();
        trackingCode.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            document.execCommand('copy');
            this.showNotification('Tracking code copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy tracking code:', err);
            this.showNotification('Failed to copy tracking code', 'error');
        }
    }
    
    showLoading(show) {
        const overlay = document.getElementById('loading-overlay');
        overlay.style.display = show ? 'flex' : 'none';
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    // Utility methods
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
    
    formatDate(timestamp) {
        return new Date(timestamp).toLocaleDateString();
    }
    
    formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString();
    }
    
    // Cleanup method
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        if (this.liveUpdateInterval) {
            clearInterval(this.liveUpdateInterval);
        }
        
        if (this.currentChart) {
            this.currentChart.destroy();
        }
        
        // Clear visitor data
        if (this.visitorData) {
            this.visitorData.clear();
        }
        
        // Dispose of all tooltips
        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        existingTooltips.forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new VisitorDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.dashboard) {
        window.dashboard.destroy();
    }
});