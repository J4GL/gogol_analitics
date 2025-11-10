#!/bin/bash

# Quick verification script for Gogol Analytics

echo "🚀 Testing Gogol Analytics..."
echo ""

# Test 1: Check if server is running
echo "1. Testing server health..."
if curl -s http://localhost:3000 > /dev/null; then
  echo "✅ Server is running"
else
  echo "❌ Server is not running"
  exit 1
fi

# Test 2: Check track.js
echo "2. Testing track.js availability..."
if curl -s http://localhost:3000/track.js | grep -q "initAnalytics"; then
  echo "✅ Tracking script is available"
else
  echo "❌ Tracking script not found"
  exit 1
fi

# Test 3: Post an event
echo "3. Testing event posting..."
TIMESTAMP=$(date +%s)000
RESPONSE=$(curl -s -X POST http://localhost:3000/api/events \
  -H "Content-Type: application/json" \
  -d "{\"timestamp\":$TIMESTAMP,\"event_type\":\"test\",\"page\":\"http://test.com\",\"referrer\":\"\",\"country\":\"US\",\"os\":\"macOS\",\"browser\":\"Test\",\"device_type\":\"PC\",\"resolution\":\"1920x1080\",\"timezone\":\"UTC\",\"page_load\":100,\"user_agent\":\"Test Agent\"}")

if echo "$RESPONSE" | grep -q "success"; then
  echo "✅ Event posted successfully"
else
  echo "❌ Failed to post event"
  echo "Response: $RESPONSE"
  exit 1
fi

# Test 4: Retrieve events
echo "4. Testing event retrieval..."
if curl -s http://localhost:3000/api/events/recent | grep -q "test"; then
  echo "✅ Events can be retrieved"
else
  echo "❌ Failed to retrieve events"
  exit 1
fi

# Test 5: Get aggregated data
echo "5. Testing aggregated data..."
NOW=$(date +%s)000
START=$((NOW - 86400000))
if curl -s "http://localhost:3000/api/traffic/aggregated?bucket=hour&start=$START&end=$NOW" | grep -q "bucket_start"; then
  echo "✅ Aggregated data is available"
else
  echo "❌ Failed to get aggregated data"
  exit 1
fi

# Test 6: Get script snippet
echo "6. Testing script snippet endpoint..."
if curl -s http://localhost:3000/api/script | grep -q "track.js"; then
  echo "✅ Script snippet endpoint works"
else
  echo "❌ Script snippet endpoint failed"
  exit 1
fi

echo ""
echo "✨ All tests passed!"
echo ""
echo "📊 Dashboard: http://localhost:3000"
echo "🔗 Fake Website: http://localhost:3000/fake_website/index.html"
