import { useState, useEffect } from 'react';
import StackedBarChart from './StackedBarChart';
import LiveVisitorTable from './LiveVisitorTable';

function TrafficTab() {
  const [trafficData, setTrafficData] = useState([]);
  const [recentEvents, setRecentEvents] = useState([]);

  useEffect(() => {
    // Fetch aggregated traffic data
    fetchTrafficData();

    // Fetch recent events
    fetchRecentEvents();

    // Setup SSE for live updates
    const eventSource = new EventSource('/api/events/stream');
    
    eventSource.addEventListener('new-event', (e) => {
      const event = JSON.parse(e.data);
      setRecentEvents(prev => [event, ...prev].slice(0, 50));
      // Refresh aggregated data when new event arrives
      fetchTrafficData();
    });

    eventSource.onerror = () => {
      console.error('SSE connection error');
    };

    return () => {
      eventSource.close();
    };
  }, []);

  const fetchTrafficData = async () => {
    try {
      const now = Date.now();
      const sevenDaysAgo = now - (7 * 24 * 60 * 60 * 1000);
      const response = await fetch(
        `/api/traffic/aggregated?bucket=day&start=${sevenDaysAgo}&end=${now}`
      );
      const data = await response.json();
      setTrafficData(data);
    } catch (error) {
      console.error('Error fetching traffic data:', error);
    }
  };

  const fetchRecentEvents = async () => {
    try {
      const response = await fetch('/api/events/recent?limit=50');
      const data = await response.json();
      setRecentEvents(data);
    } catch (error) {
      console.error('Error fetching recent events:', error);
    }
  };

  return (
    <div>
      <StackedBarChart data={trafficData} />
      <LiveVisitorTable events={recentEvents} />
    </div>
  );
}

export default TrafficTab;
