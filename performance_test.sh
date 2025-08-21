#!/bin/bash

# Performance test script for Traffic Analytics

echo "🚀 Performance Test for Traffic Analytics"
echo "========================================"

# Test API endpoints
echo
echo "📊 Testing API Performance:"

echo -n "  /api/stats: "
time_stats=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost:8000/api/stats?hours=24")
echo "${time_stats}s"

echo -n "  /api/collect: "
time_collect=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost:8000/api/collect?data=eyJwYWdlIjoiL3Rlc3QiLCJ0aW1lem9uZSI6IkFtZXJpY2EvTmV3X1lvcmsifQ==")
echo "${time_collect}s"

echo -n "  /collect.js: "
time_js=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost:8000/collect.js")
echo "${time_js}s"

echo -n "  Dashboard: "
time_dashboard=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost:8000/")
echo "${time_dashboard}s"

echo
echo "🧪 Testing Multiple Requests:"

# Test multiple collect requests
echo -n "  10 collect requests: "
start_time=$(date +%s.%N)
for i in {1..10}; do
    curl -s "http://localhost:8000/api/collect?data=eyJwYWdlIjoiL3Rlc3QtJGkiLCJ0aW1lem9uZSI6IkFtZXJpY2EvTmV3X1lvcmsifQ==" > /dev/null &
done
wait
end_time=$(date +%s.%N)
duration=$(echo "$end_time - $start_time" | bc)
echo "${duration}s"

echo
echo "📈 Database Status:"
event_count=$(sqlite3 database.sqlite "SELECT COUNT(*) FROM events;")
echo "  Total events: $event_count"

echo
echo "✅ Performance Test Complete!"
echo
echo "Expected performance:"
echo "  - API endpoints: < 0.1s"
echo "  - Dashboard load: < 0.5s"
echo "  - JavaScript load: < 0.1s"
