<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? 'stats';
    
    switch ($action) {
        case 'stats':
            getVisitorStats($db);
            break;
        case 'live':
            getLiveVisitors($db);
            break;
        case 'chart':
            getChartData($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $_ENV['DEBUG'] === 'true' ? $e->getMessage() : 'Please try again later'
    ]);
}

function getVisitorStats($db) {
    $timeframe = $_GET['timeframe'] ?? '24h';
    $interval = getTimeInterval($timeframe);
    
    // Get visitor statistics
    $stats_query = "
        SELECT 
            COUNT(DISTINCT CASE WHEN NOT v.is_bot THEN e.visitor_id END) as unique_visitors,
            COUNT(DISTINCT CASE WHEN v.is_bot THEN e.visitor_id END) as bots,
            COUNT(DISTINCT CASE WHEN NOT v.is_bot AND total_visits = 1 THEN e.visitor_id END) as new_visitors,
            COUNT(DISTINCT CASE WHEN NOT v.is_bot AND total_visits > 1 THEN e.visitor_id END) as returning_visitors,
            COUNT(*) as total_events,
            COUNT(CASE WHEN event_type = 'pageview' THEN 1 END) as pageviews
        FROM events e
        JOIN visitors v ON e.visitor_id = v.visitor_id
        WHERE e.created_at >= datetime('now', '$interval')
    ";
    
    $stats_stmt = $db->query($stats_query);
    $stats = $stats_stmt->fetch();
    
    // Get top pages
    $pages_query = "
        SELECT 
            page,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_id) as unique_visitors
        FROM events 
        WHERE created_at >= datetime('now', '$interval')
        AND event_type = 'pageview'
        AND NOT is_bot
        GROUP BY page
        ORDER BY views DESC
        LIMIT 10
    ";
    
    $pages_stmt = $db->query($pages_query);
    $top_pages = $pages_stmt->fetchAll();
    
    // Get referrers - using SQLite functions
    $referrers_query = "
        SELECT 
            CASE 
                WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                ELSE substr(referrer, 1, instr(substr(referrer, 9), '/') + 7)
            END as referrer_domain,
            COUNT(*) as visits
        FROM events 
        WHERE created_at >= datetime('now', '$interval')
        AND event_type = 'pageview'
        AND NOT is_bot
        GROUP BY referrer_domain
        ORDER BY visits DESC
        LIMIT 10
    ";
    
    $referrers_stmt = $db->query($referrers_query);
    $top_referrers = $referrers_stmt->fetchAll();
    
    echo json_encode([
        'stats' => $stats,
        'top_pages' => $top_pages,
        'top_referrers' => $top_referrers,
        'timeframe' => $timeframe
    ]);
}

function getLiveVisitors($db) {
    // Get recent visitors (last 10 seconds)
    $live_query = "
        SELECT 
            e.visitor_id,
            e.page,
            e.referrer,
            e.country,
            e.os,
            e.browser,
            e.device_type,
            e.resolution,
            e.timezone,
            e.page_load_time,
            e.is_bot,
            e.user_agent,
            e.ip_address,
            e.created_at,
            e.event_type,
            e.element_tag_name,
            e.element_id,
            e.element_class,
            e.element_text,
            e.element_type,
            e.element_href,
            v.total_visits,
            CASE 
                WHEN v.total_visits = 1 THEN 'new'
                WHEN v.is_bot THEN 'bot'
                ELSE 'returning'
            END as visitor_type
        FROM events e
        JOIN visitors v ON e.visitor_id = v.visitor_id
        WHERE e.created_at >= datetime('now', '-10 seconds')
        ORDER BY e.created_at DESC
        LIMIT 100
    ";
    
    $live_stmt = $db->query($live_query);
    $live_visitors = $live_stmt->fetchAll();
    
    echo json_encode([
        'visitors' => $live_visitors,
        'count' => count($live_visitors)
    ]);
}

function getChartData($db) {
    $timeframe = $_GET['timeframe'] ?? '24h';
    $interval = getTimeInterval($timeframe);
    $groupBy = getGroupByFormat($timeframe);
    
    // Generate complete time series first
    $timeSeriesData = generateTimeSeriesData($timeframe);
    
    $chart_query = "
        SELECT 
            strftime('$groupBy', created_at) as time_period,
            COUNT(DISTINCT CASE WHEN NOT v.is_bot AND v.total_visits = 1 THEN e.visitor_id END) as new_visitors,
            COUNT(DISTINCT CASE WHEN NOT v.is_bot AND v.total_visits > 1 THEN e.visitor_id END) as returning_visitors,
            COUNT(DISTINCT CASE WHEN v.is_bot THEN e.visitor_id END) as bots
        FROM events e
        JOIN visitors v ON e.visitor_id = v.visitor_id
        WHERE e.created_at >= datetime('now', '$interval')
        AND e.event_type = 'pageview'
        GROUP BY time_period
        ORDER BY time_period ASC
    ";
    
    $chart_stmt = $db->query($chart_query);
    $actual_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create lookup array for actual data
    $data_lookup = [];
    foreach ($actual_data as $row) {
        $data_lookup[$row['time_period']] = $row;
    }
    
    // Merge with complete time series
    $chart_data = [];
    foreach ($timeSeriesData as $time_period) {
        if (isset($data_lookup[$time_period])) {
            $chart_data[] = $data_lookup[$time_period];
        } else {
            $chart_data[] = [
                'time_period' => $time_period,
                'new_visitors' => 0,
                'returning_visitors' => 0,
                'bots' => 0
            ];
        }
    }
    
    echo json_encode([
        'chart_data' => $chart_data,
        'timeframe' => $timeframe
    ]);
}

function getTimeInterval($timeframe) {
    switch ($timeframe) {
        case '24h': return '-24 hours';
        case '7d': return '-7 days';
        case '30d': return '-30 days';
        default: return '-24 hours';
    }
}

function getGroupByFormat($timeframe) {
    switch ($timeframe) {
        case '24h': return '%Y-%m-%d %H';    // By hour
        case '7d': return '%Y-%m-%d';        // By day
        case '30d': return '%Y-%m-%d';       // By day
        default: return '%Y-%m-%d %H';
    }
}

function generateTimeSeriesData($timeframe) {
    $data = [];
    $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
    
    switch ($timeframe) {
        case '24h':
            // Generate 24 hours (current hour is the last bar)
            for ($i = 23; $i >= 0; $i--) {
                $time = clone $now;
                $time->sub(new DateInterval("PT{$i}H"));
                $data[] = $time->format('Y-m-d H');
            }
            break;
            
        case '7d':
            // Generate 7 days (today is the last bar)
            for ($i = 6; $i >= 0; $i--) {
                $time = clone $now;
                $time->sub(new DateInterval("P{$i}D"));
                $data[] = $time->format('Y-m-d');
            }
            break;
            
        case '30d':
            // Generate 30 days (today is the last bar)
            for ($i = 29; $i >= 0; $i--) {
                $time = clone $now;
                $time->sub(new DateInterval("P{$i}D"));
                $data[] = $time->format('Y-m-d');
            }
            break;
            
        default:
            // Default to 24h
            for ($i = 23; $i >= 0; $i--) {
                $time = clone $now;
                $time->sub(new DateInterval("PT{$i}H"));
                $data[] = $time->format('Y-m-d H');
            }
    }
    
    return $data;
}
?>