<?php

namespace VisitorTracking\Services;

use VisitorTracking\Models\Visitor;
use VisitorTracking\Models\Event;

class AnalyticsService
{
    private Visitor $visitorModel;
    private Event $eventModel;
    private array $config;

    public function __construct()
    {
        $this->visitorModel = new Visitor();
        $this->eventModel = new Event();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Get comprehensive analytics data for dashboard
     */
    public function getAnalyticsData(array $params = []): array
    {
        try {
            $period = $params['period'] ?? 'daily';
            $startDate = $params['start_date'] ?? null;
            $endDate = $params['end_date'] ?? null;

            // Get visitor analytics for charts
            $visitorData = $this->visitorModel->getAnalyticsData($period, $startDate, $endDate);
            
            // Get event analytics
            $eventData = $this->eventModel->getEventsByPeriod($period, $startDate, $endDate);
            
            // Get overall statistics
            $visitorStats = $this->visitorModel->getVisitorStats();
            $eventStats = $this->eventModel->getEventStats();
            
            // Get page view statistics
            $pageViewStats = $this->eventModel->getPageViewStats();
            
            // Get referrer statistics
            $referrerStats = $this->eventModel->getReferrerStats();

            return [
                'status' => 'success',
                'data' => [
                    'visitor_data' => $visitorData,
                    'event_data' => $this->formatEventData($eventData),
                    'visitor_stats' => $visitorStats,
                    'event_stats' => $eventStats,
                    'page_views' => $pageViewStats,
                    'referrers' => $referrerStats,
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

        } catch (\Exception $e) {
            error_log("Analytics error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve analytics data',
                'error_code' => 'ANALYTICS_ERROR'
            ];
        }
    }

    /**
     * Get chart data specifically formatted for Chart.js
     */
    public function getChartData(array $params = []): array
    {
        try {
            $period = $params['period'] ?? 'daily';
            $startDate = $params['start_date'] ?? null;
            $endDate = $params['end_date'] ?? null;

            $visitorData = $this->visitorModel->getAnalyticsData($period, $startDate, $endDate);

            // Format data for stacked bar chart
            $labels = [];
            $botData = [];
            $newVisitorData = [];
            $returningVisitorData = [];

            foreach ($visitorData as $item) {
                $labels[] = $this->formatDateLabel($item['date'], $period);
                $botData[] = $item['bots'];
                $newVisitorData[] = $item['new_visitors'];
                $returningVisitorData[] = $item['returning_visitors'];
            }

            return [
                'status' => 'success',
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Bots',
                            'data' => $botData,
                            'backgroundColor' => '#dc3545',
                            'borderColor' => '#dc3545',
                            'borderWidth' => 1
                        ],
                        [
                            'label' => 'New Visitors',
                            'data' => $newVisitorData,
                            'backgroundColor' => '#28a745',
                            'borderColor' => '#28a745',
                            'borderWidth' => 1
                        ],
                        [
                            'label' => 'Returning Visitors',
                            'data' => $returningVisitorData,
                            'backgroundColor' => '#007bff',
                            'borderColor' => '#007bff',
                            'borderWidth' => 1
                        ]
                    ]
                ],
                'chart_options' => [
                    'responsive' => true,
                    'scales' => [
                        'x' => [
                            'stacked' => true,
                        ],
                        'y' => [
                            'stacked' => true,
                            'beginAtZero' => true
                        ]
                    ],
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'Visitor Analytics - ' . ucfirst($period) . ' View'
                        ]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            error_log("Chart data error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to generate chart data'
            ];
        }
    }

    /**
     * Get real-time dashboard statistics
     */
    public function getDashboardStats(): array
    {
        try {
            // Get overall stats
            $visitorStats = $this->visitorModel->getVisitorStats();
            $eventStats = $this->eventModel->getEventStats();
            
            // Get today's stats
            $todayStart = strtotime('today') * 1000;
            $todayEnd = strtotime('tomorrow') * 1000 - 1;
            
            $todayVisitors = $this->visitorModel->getAnalyticsData('daily', date('Y-m-d'), date('Y-m-d'));
            $todayEvents = $this->eventModel->getEventsByPeriod('daily', date('Y-m-d'), date('Y-m-d'));

            // Calculate today's totals
            $todayTotals = [
                'visitors' => 0,
                'new_visitors' => 0,
                'returning_visitors' => 0,
                'bots' => 0,
                'pageviews' => 0,
                'events' => 0
            ];

            if (!empty($todayVisitors)) {
                $today = $todayVisitors[0];
                $todayTotals['visitors'] = $today['total'];
                $todayTotals['new_visitors'] = $today['new_visitors'];
                $todayTotals['returning_visitors'] = $today['returning_visitors'];
                $todayTotals['bots'] = $today['bots'];
            }

            foreach ($todayEvents as $event) {
                $todayTotals['events'] += $event['count'];
                if ($event['event_type'] === 'pageview') {
                    $todayTotals['pageviews'] += $event['count'];
                }
            }

            // Get live visitors (last 30 minutes)
            $liveVisitors = $this->visitorModel->getLiveVisitors(30);
            $activeVisitors = $this->getActiveVisitorCount();

            return [
                'status' => 'success',
                'stats' => [
                    'total' => $visitorStats,
                    'events' => $eventStats,
                    'today' => $todayTotals,
                    'live' => [
                        'active_visitors' => $activeVisitors,
                        'recent_activity' => array_slice($liveVisitors, 0, 10)
                    ]
                ]
            ];

        } catch (\Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard statistics'
            ];
        }
    }

    /**
     * Get live visitors feed
     */
    public function getLiveVisitorFeed(int $minutes = 5): array
    {
        try {
            $liveVisitors = $this->visitorModel->getLiveVisitors($minutes);
            $recentEvents = $this->eventModel->getRecentEvents($minutes);
            
            // Format for display with complete information for tooltips
            $formattedVisitors = [];
            foreach ($liveVisitors as $visitor) {
                // Parse event_data if it exists and is JSON
                $eventData = null;
                if (!empty($visitor['event_data'])) {
                    $eventData = json_decode($visitor['event_data'], true);
                }
                
                $formattedVisitors[] = [
                    'visitor_uuid' => $visitor['visitor_uuid'], // Keep full UUID for tooltip
                    'visitor_type' => $visitor['visitor_type'],
                    'page_url' => $visitor['page_url'],
                    'page_title' => $visitor['page_title'] ?: 'Unknown',
                    'event_type' => $visitor['event_type'],
                    'timestamp' => $visitor['timestamp'],
                    'time_ago' => $this->timeAgo($visitor['timestamp']),
                    'referrer' => $visitor['referrer'] ?? null,
                    'event_data' => $eventData
                ];
            }

            return [
                'status' => 'success',
                'live_visitors' => $formattedVisitors,
                'active_count' => $this->getActiveVisitorCount(),
                'total_events' => count($recentEvents),
                'last_updated' => time() * 1000
            ];

        } catch (\Exception $e) {
            error_log("Live visitor feed error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve live visitor feed'
            ];
        }
    }

    /**
     * Get page performance analytics
     */
    public function getPageAnalytics(): array
    {
        try {
            $pageViews = $this->eventModel->getPageViewStats();
            $referrers = $this->eventModel->getReferrerStats();
            
            // Format page views
            $formattedPages = [];
            foreach ($pageViews as $page) {
                $formattedPages[] = [
                    'url' => $page['page_url'],
                    'title' => $page['page_title'] ?: 'Unknown',
                    'views' => (int)$page['views'],
                    'unique_visitors' => (int)$page['unique_visitors'],
                    'bounce_rate' => $this->calculateBounceRate($page['page_url'])
                ];
            }

            // Format referrers
            $formattedReferrers = [];
            foreach ($referrers as $referrer) {
                $domain = $this->extractDomain($referrer['referrer_source']);
                $formattedReferrers[] = [
                    'source' => $referrer['referrer_source'],
                    'domain' => $domain,
                    'visits' => (int)$referrer['visits'],
                    'unique_visitors' => (int)$referrer['unique_visitors']
                ];
            }

            return [
                'status' => 'success',
                'pages' => $formattedPages,
                'referrers' => $formattedReferrers
            ];

        } catch (\Exception $e) {
            error_log("Page analytics error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve page analytics'
            ];
        }
    }

    /**
     * Format event data for analytics
     */
    private function formatEventData(array $eventData): array
    {
        $formatted = [];
        $eventTypes = ['pageview', 'click', 'scroll', 'custom'];
        
        // Group by date
        $groupedData = [];
        foreach ($eventData as $event) {
            $date = $event['date'];
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = array_fill_keys($eventTypes, 0);
                $groupedData[$date]['total'] = 0;
            }
            
            $groupedData[$date][$event['event_type']] = (int)$event['count'];
            $groupedData[$date]['total'] += (int)$event['count'];
        }
        
        // Convert to array
        foreach ($groupedData as $date => $data) {
            $formatted[] = array_merge(['date' => $date], $data);
        }
        
        return $formatted;
    }

    /**
     * Format date labels for charts
     */
    private function formatDateLabel(string $date, string $period): string
    {
        try {
            $timestamp = strtotime($date);
            
            switch ($period) {
                case 'hourly':
                    return date('H:i', $timestamp);
                case 'daily':
                    return date('M j', $timestamp);
                case 'weekly':
                    return 'Week ' . date('W', $timestamp);
                case 'monthly':
                    return date('M Y', $timestamp);
                default:
                    return date('M j', $timestamp);
            }
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Get active visitor count (visitors with activity in last 5 minutes)
     */
    private function getActiveVisitorCount(): int
    {
        try {
            $threshold = (time() - 300) * 1000; // 5 minutes ago in milliseconds
            
            $liveVisitors = $this->visitorModel->getLiveVisitors(5);
            
            // Count unique visitors
            $uniqueVisitors = [];
            foreach ($liveVisitors as $visitor) {
                $uniqueVisitors[$visitor['visitor_uuid']] = true;
            }
            
            return count($uniqueVisitors);
            
        } catch (\Exception $e) {
            error_log("Error getting active visitor count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate time ago from timestamp
     */
    private function timeAgo(int $timestamp): string
    {
        $now = time() * 1000;
        $diff = $now - $timestamp;
        
        $seconds = floor($diff / 1000);
        
        if ($seconds < 60) {
            return $seconds . 's ago';
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . 'm ago';
        }
        
        $hours = floor($minutes / 60);
        if ($hours < 24) {
            return $hours . 'h ago';
        }
        
        $days = floor($hours / 24);
        return $days . 'd ago';
    }

    /**
     * Calculate bounce rate for a page (simplified)
     */
    private function calculateBounceRate(string $pageUrl): float
    {
        // This is a simplified bounce rate calculation
        // In a real implementation, you'd want to track sessions more comprehensively
        try {
            // For now, return a placeholder value
            // TODO: Implement proper bounce rate calculation
            return 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain(string $url): string
    {
        if ($url === 'Direct' || empty($url)) {
            return 'Direct';
        }
        
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
    }

    /**
     * Get trending pages (pages with increasing views)
     */
    public function getTrendingPages(): array
    {
        try {
            // Get page views for last 7 days and previous 7 days
            $currentWeekStart = date('Y-m-d', strtotime('-7 days'));
            $currentWeekEnd = date('Y-m-d');
            $previousWeekStart = date('Y-m-d', strtotime('-14 days'));
            $previousWeekEnd = date('Y-m-d', strtotime('-8 days'));
            
            // This would require more complex SQL queries
            // For now, return the top pages
            $pageViews = $this->eventModel->getPageViewStats(7);
            
            return [
                'status' => 'success',
                'trending_pages' => array_slice($pageViews, 0, 5)
            ];
            
        } catch (\Exception $e) {
            error_log("Trending pages error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve trending pages'
            ];
        }
    }
}