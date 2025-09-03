// Dashboard JavaScript
class GogolDashboard {
    constructor() {
        this.chart = null;
        this.refreshInterval = null;
        this.liveData = [];
        this.init();
    }

    init() {
        this.setupTabs();
        this.setupTimeframeSelector();
        this.setupCopyButton();
        this.loadDashboardData();
        this.startAutoRefresh();
    }

    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanels = document.querySelectorAll('.tab-panel');

        // Only set up if tabs exist
        if (tabButtons.length > 0 && tabPanels.length > 0) {
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetTab = button.dataset.tab;
                    
                    // Update active button
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Update active panel
                    tabPanels.forEach(panel => panel.classList.remove('active'));
                    const targetPanel = document.getElementById(targetTab);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                });
            });
        }
    }

    setupTimeframeSelector() {
        const timeframeSelect = document.getElementById('timeRange');
        if (timeframeSelect) {
            timeframeSelect.addEventListener('change', () => {
                this.loadDashboardData();
            });
        }
    }

    setupCopyButton() {
        const copyButton = document.getElementById('copyCode');
        const codeElement = document.getElementById('trackingCode');
        
        // Only set up if both elements exist
        if (copyButton && codeElement) {
            copyButton.addEventListener('click', () => {
                const text = codeElement.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    copyButton.textContent = 'Copied!';
                    copyButton.classList.add('copied');
                    setTimeout(() => {
                        copyButton.textContent = 'Copy Code';
                        copyButton.classList.remove('copied');
                    }, 2000);
                });
            });
        }
    }

    async loadDashboardData() {
        const timeframeElement = document.getElementById('timeRange') || document.getElementById('timeframe');
        const timeframe = timeframeElement ? timeframeElement.value : '24h';
        
        try {
            // Load stats
            await this.loadStats(timeframe);
            
            // Load chart data
            await this.loadChartData(timeframe);
            
            // Load live visitors
            await this.loadLiveVisitors();
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    async loadStats(timeframe) {
        const response = await fetch(`api/dashboard?action=stats&timeframe=${timeframe}`);
        if (!response.ok) {
            console.error('Failed to load stats:', response.status, response.statusText);
            return;
        }
        const data = await response.json();
        console.log('Loaded stats:', data.stats);
        
        // Update stat cards - handle both camelCase and kebab-case IDs
        const totalVisitors = parseInt(data.stats.total_visitors || 0);
        const bots = parseInt(data.stats.bots || 0);
        
        // Try both ID formats for compatibility
        const updateElement = (id1, id2, value) => {
            const el = document.getElementById(id1) || document.getElementById(id2);
            if (el) el.textContent = value;
        };
        
        updateElement('uniqueVisitors', 'unique-visitors', totalVisitors.toLocaleString());
        updateElement('totalVisitors', 'total-visitors', totalVisitors.toLocaleString()); // Show actual total
        updateElement('newVisitors', 'new-visitors', parseInt(data.stats.new_visitors || 0).toLocaleString());
        updateElement('returningVisitors', 'returning-visitors', parseInt(data.stats.returning_visitors || 0).toLocaleString());
        updateElement('bots', 'bots', parseInt(data.stats.bots || 0).toLocaleString());
        
        // Also update total page views if available
        if (document.getElementById('totalPageViews') && data.stats.total_events) {
            document.getElementById('totalPageViews').textContent = 
                parseInt(data.stats.total_events || 0).toLocaleString();
        }
        
        // Update top pages if element exists
        const topPagesList = document.getElementById('topPagesList');
        if (topPagesList) {
            topPagesList.innerHTML = '';
            data.top_pages.forEach(page => {
                const li = document.createElement('li');
                const url = new URL(page.page);
                li.innerHTML = `
                    <span>${url.pathname}</span>
                    <span>${page.views}</span>
                `;
                topPagesList.appendChild(li);
            });
        }
        
        // Update top referrers if element exists
        const topReferrersList = document.getElementById('topReferrersList');
        if (topReferrersList) {
            topReferrersList.innerHTML = '';
            data.top_referrers.forEach(referrer => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <span>${referrer.referrer_domain}</span>
                    <span>${referrer.visits}</span>
                `;
                topReferrersList.appendChild(li);
            });
        }
    }

    async loadChartData(timeframe) {
        const response = await fetch(`api/dashboard?action=chart&timeframe=${timeframe}`);
        const data = await response.json();
        
        this.updateChart(data.chart_data);
    }

    updateChart(chartData) {
        // Try both possible canvas IDs
        const canvas = document.getElementById('trafficChart') || document.getElementById('visitorChart');
        if (!canvas) {
            console.error('Chart canvas not found');
            return;
        }
        const ctx = canvas.getContext('2d');
        
        // Prepare data for stacked bar chart
        const labels = chartData.map(item => {
            const timeframeElement = document.getElementById('timeRange') || document.getElementById('timeframe');
            const timeframe = timeframeElement ? timeframeElement.value : '24h';
            
            if (timeframe === '24h') {
                // Parse hour format: "2024-01-15 14" -> show as "14h"
                const parts = item.time_period.split(' ');
                const hour = parseInt(parts[1]);
                return hour.toString().padStart(2, '0') + 'h';
            } else {
                // Parse date format: "2024-01-15" -> show as "15 Jan"
                const date = new Date(item.time_period + 'T00:00:00');
                return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
            }
        });
        
        const datasets = [
            {
                label: 'Returning Visitors',
                data: chartData.map(item => parseInt(item.returning_visitors || 0)),
                backgroundColor: '#3b82f6',
                borderColor: '#3b82f6',
                borderWidth: 0
            },
            {
                label: 'New Visitors',
                data: chartData.map(item => parseInt(item.new_visitors || 0)),
                backgroundColor: '#10b981',
                borderColor: '#10b981',
                borderWidth: 0
            },
            {
                label: 'Bots',
                data: chartData.map(item => parseInt(item.bots || 0)),
                backgroundColor: '#ef4444',
                borderColor: '#ef4444',
                borderWidth: 0
            }
        ];
        
        // Destroy existing chart if it exists
        if (this.chart) {
            this.chart.destroy();
        }
        
        // Create new chart
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    async loadLiveVisitors() {
        const response = await fetch('api/dashboard?action=live');
        const data = await response.json();
        
        this.liveData = data.visitors;
        this.updateLiveTable(data.visitors);
    }

    updateLiveTable(visitors) {
        const tbody = document.getElementById('liveTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        visitors.slice(0, 50).forEach((visitor, index) => {
            const tr = document.createElement('tr');
            const time = new Date(visitor.created_at);
            const url = new URL(visitor.page);
            
            // Determine visitor type badge
            let typeBadge = '';
            if (visitor.visitor_type === 'new') {
                typeBadge = '<span class="visitor-type new">New</span>';
            } else if (visitor.visitor_type === 'returning') {
                typeBadge = '<span class="visitor-type returning">Returning</span>';
            } else if (visitor.visitor_type === 'bot') {
                typeBadge = '<span class="visitor-type bot">Bot</span>';
            }
            
            // Format event details for display
            let eventDisplay = visitor.event_type;
            if (visitor.event_type === 'click' && visitor.element_text) {
                eventDisplay = `click: "${visitor.element_text}"`;
            } else if (visitor.event_type === 'click' && visitor.element_tag_name) {
                eventDisplay = `click: ${visitor.element_tag_name}`;
            }
            
            tr.innerHTML = `
                <td>${time.toLocaleTimeString()}</td>
                <td>${url.pathname}</td>
                <td>${eventDisplay}</td>
                <td>${typeBadge}</td>
                <td>${visitor.country || 'Unknown'}</td>
            `;
            
            // Add hover event for tooltip
            tr.addEventListener('mouseenter', (e) => this.showTooltip(e, visitor));
            tr.addEventListener('mouseleave', () => this.hideTooltip());
            
            tbody.appendChild(tr);
        });
    }

    showTooltip(event, visitor) {
        const tooltip = document.getElementById('tooltip');
        const rect = event.target.getBoundingClientRect();
        
        // Format tooltip content
        const referrer = visitor.referrer || 'Direct';
        const loadTime = visitor.page_load_time ? `${visitor.page_load_time}ms` : 'N/A';
        
        let tooltipContent = `
            <div class="tooltip-row"><span class="tooltip-label">Visitor ID:</span> ${visitor.visitor_id.substring(0, 8)}...</div>
            <div class="tooltip-row"><span class="tooltip-label">Page:</span> ${visitor.page}</div>
            <div class="tooltip-row"><span class="tooltip-label">Event:</span> ${visitor.event_type}</div>
        `;
        
        // Add click event details if available
        if (visitor.event_type === 'click') {
            if (visitor.element_text) {
                tooltipContent += `<div class="tooltip-row"><span class="tooltip-label">Element Text:</span> ${visitor.element_text}</div>`;
            }
            if (visitor.element_tag_name) {
                tooltipContent += `<div class="tooltip-row"><span class="tooltip-label">Tag:</span> ${visitor.element_tag_name}</div>`;
            }
            if (visitor.element_type) {
                tooltipContent += `<div class="tooltip-row"><span class="tooltip-label">Type:</span> ${visitor.element_type}</div>`;
            }
            if (visitor.element_href) {
                tooltipContent += `<div class="tooltip-row"><span class="tooltip-label">Link:</span> ${visitor.element_href}</div>`;
            }
            if (visitor.element_id) {
                tooltipContent += `<div class="tooltip-row"><span class="tooltip-label">Element ID:</span> ${visitor.element_id}</div>`;
            }
        }
        
        tooltipContent += `
            <div class="tooltip-row"><span class="tooltip-label">Referrer:</span> ${referrer}</div>
            <div class="tooltip-row"><span class="tooltip-label">Country:</span> ${visitor.country || 'Unknown'}</div>
            <div class="tooltip-row"><span class="tooltip-label">OS:</span> ${visitor.os || 'Unknown'}</div>
            <div class="tooltip-row"><span class="tooltip-label">Browser:</span> ${visitor.browser || 'Unknown'}</div>
            <div class="tooltip-row"><span class="tooltip-label">Device:</span> ${visitor.device_type}</div>
            <div class="tooltip-row"><span class="tooltip-label">Resolution:</span> ${visitor.resolution || 'N/A'}</div>
            <div class="tooltip-row"><span class="tooltip-label">Timezone:</span> ${visitor.timezone || 'N/A'}</div>
            <div class="tooltip-row"><span class="tooltip-label">Load Time:</span> ${loadTime}</div>
            <div class="tooltip-row"><span class="tooltip-label">Total Visits:</span> ${visitor.total_visits}</div>
            <div class="tooltip-row"><span class="tooltip-label">IP:</span> ${visitor.ip_address}</div>
            <div class="tooltip-row"><span class="tooltip-label">User Agent:</span> ${visitor.user_agent}</div>
        `;
        
        tooltip.innerHTML = tooltipContent;
        
        // Position tooltip
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.bottom + 10) + 'px';
        
        // Adjust if tooltip goes off screen
        const tooltipRect = tooltip.getBoundingClientRect();
        if (tooltipRect.right > window.innerWidth) {
            tooltip.style.left = (window.innerWidth - tooltipRect.width - 20) + 'px';
        }
        if (tooltipRect.bottom > window.innerHeight) {
            tooltip.style.top = (rect.top - tooltipRect.height - 10) + 'px';
        }
        
        tooltip.classList.add('show');
    }

    hideTooltip() {
        const tooltip = document.getElementById('tooltip');
        tooltip.classList.remove('show');
    }

    startAutoRefresh() {
        // Clear existing interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Refresh every 10 seconds
        this.refreshInterval = setInterval(() => {
            this.loadLiveVisitors();
            const timeframeElement = document.getElementById('timeRange') || document.getElementById('timeframe');
            const timeframe = timeframeElement ? timeframeElement.value : '24h';
            this.loadStats(timeframe);
            // Also refresh the chart to show new visitors
            this.loadChartData(timeframe);
        }, 10000);
    }

    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        if (this.chart) {
            this.chart.destroy();
        }
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.gogolDashboard = new GogolDashboard();
});