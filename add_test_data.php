<?php
require_once 'api/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Adding historical test data...\n";
    
    // Generate test data for the last 7 days
    $testVisitors = [
        ['id' => 'testuser1', 'is_bot' => false, 'total_visits' => 1], // New user
        ['id' => 'testuser2', 'is_bot' => false, 'total_visits' => 3], // Returning user
        ['id' => 'testuser3', 'is_bot' => false, 'total_visits' => 5], // Returning user
        ['id' => 'botuser1', 'is_bot' => true, 'total_visits' => 1],   // Bot
        ['id' => 'testuser4', 'is_bot' => false, 'total_visits' => 1], // New user
        ['id' => 'testuser5', 'is_bot' => false, 'total_visits' => 2], // Returning user
    ];
    
    $testPages = [
        'http://localhost:8000/test/',
        'http://localhost:8000/test/about.html',
        'http://localhost:8000/test/products.html',
        'http://localhost:8000/test/contact.html',
    ];
    
    $countries = ['US', 'GB', 'FR', 'DE', 'CA', 'AU'];
    $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
    $oses = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];
    $devices = ['PC', 'MOBILE'];
    
    // Add visitors first
    foreach ($testVisitors as $visitor) {
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO visitors 
            (visitor_id, ip_address, user_agent, country, os, browser, device_type, is_bot, total_visits, first_seen, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', '-' || ? || ' hours'), datetime('now'))
        ");
        
        $stmt->execute([
            $visitor['id'],
            '192.168.1.' . rand(1, 255),
            'Mozilla/5.0 (Test User Agent)',
            $countries[array_rand($countries)],
            $oses[array_rand($oses)],
            $browsers[array_rand($browsers)],
            $devices[array_rand($devices)],
            $visitor['is_bot'],
            $visitor['total_visits'],
            rand(1, 168) // Random hours ago (1 week = 168 hours)
        ]);
    }
    
    echo "Added test visitors\n";
    
    // Add events for each day in the last 7 days
    for ($day = 0; $day < 7; $day++) {
        $eventsPerDay = rand(5, 20); // Random number of events per day
        
        for ($event = 0; $event < $eventsPerDay; $event++) {
            $visitor = $testVisitors[array_rand($testVisitors)];
            $page = $testPages[array_rand($testPages)];
            
            // Create timestamp for this day
            $hoursAgo = ($day * 24) + rand(0, 23); // Day offset + random hour
            $timestamp = time() - ($hoursAgo * 3600); // Convert to Unix timestamp
            $timestampMs = $timestamp * 1000; // Convert to milliseconds
            
            $stmt = $db->prepare("
                INSERT INTO events 
                (visitor_id, timestamp, event_type, page, referrer, country, os, browser, 
                 device_type, resolution, timezone, page_load_time, is_bot, user_agent, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', '-' || ? || ' hours'))
            ");
            
            $stmt->execute([
                $visitor['id'],
                $timestampMs,
                'pageview',
                $page,
                rand(0, 1) ? 'https://google.com' : '',
                $countries[array_rand($countries)],
                $oses[array_rand($oses)],
                $browsers[array_rand($browsers)],
                $devices[array_rand($devices)],
                '1920x1080',
                'America/New_York',
                rand(50, 500),
                $visitor['is_bot'],
                'Mozilla/5.0 (Test User Agent)',
                '192.168.1.' . rand(1, 255),
                $hoursAgo
            ]);
        }
        
        echo "Added events for day -$day\n";
    }
    
    // Add some events throughout the day (for 24h view)
    for ($hour = 0; $hour < 24; $hour++) {
        if (rand(0, 1)) { // 50% chance of having events this hour
            $eventsThisHour = rand(1, 5);
            
            for ($event = 0; $event < $eventsThisHour; $event++) {
                $visitor = $testVisitors[array_rand($testVisitors)];
                $page = $testPages[array_rand($testPages)];
                
                $timestamp = time() - ($hour * 3600) + rand(0, 3599); // Random minute in the hour
                $timestampMs = $timestamp * 1000;
                
                $stmt = $db->prepare("
                    INSERT INTO events 
                    (visitor_id, timestamp, event_type, page, referrer, country, os, browser, 
                     device_type, resolution, timezone, page_load_time, is_bot, user_agent, ip_address, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', '-' || ? || ' hours'))
                ");
                
                $stmt->execute([
                    $visitor['id'],
                    $timestampMs,
                    'pageview',
                    $page,
                    rand(0, 1) ? 'https://google.com' : '',
                    $countries[array_rand($countries)],
                    $oses[array_rand($oses)],
                    $browsers[array_rand($browsers)],
                    $devices[array_rand($devices)],
                    '1920x1080',
                    'America/New_York',
                    rand(50, 500),
                    $visitor['is_bot'],
                    'Mozilla/5.0 (Test User Agent)',
                    '192.168.1.' . rand(1, 255),
                    $hour
                ]);
            }
        }
    }
    
    echo "Added hourly events for 24h view\n";
    
    // Show final counts
    $eventCount = $db->query("SELECT COUNT(*) as count FROM events")->fetch()['count'];
    $visitorCount = $db->query("SELECT COUNT(*) as count FROM visitors")->fetch()['count'];
    
    echo "\nTest data added successfully!\n";
    echo "Total events: $eventCount\n";
    echo "Total visitors: $visitorCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>