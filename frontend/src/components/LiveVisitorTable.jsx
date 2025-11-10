import * as Tooltip from '@radix-ui/react-tooltip';

function LiveVisitorTable({ events }) {
  const formatTime = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit', 
      second: '2-digit' 
    });
  };

  const formatDate = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US');
  };

  return (
    <div className="table-container">
      <h2 className="table-title">Live Visitors</h2>
      {events.length > 0 ? (
        <Tooltip.Provider delayDuration={0}>
          <table className="events-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Page View</th>
                <th>Event Tracked</th>
              </tr>
            </thead>
            <tbody>
              {events.map((event, index) => (
                <Tooltip.Root key={event.id || index}>
                  <Tooltip.Trigger asChild>
                    <tr>
                      <td>{formatTime(event.timestamp)}</td>
                      <td>{event.page}</td>
                      <td>{event.event_type}</td>
                    </tr>
                  </Tooltip.Trigger>
                  <Tooltip.Portal>
                    <Tooltip.Content className="tooltip-content" sideOffset={5}>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Timestamp:</span>
                        <span className="tooltip-value">{formatDate(event.timestamp)} {formatTime(event.timestamp)}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Visitor ID:</span>
                        <span className="tooltip-value">{event.visitor_id?.substring(0, 16)}...</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Event Type:</span>
                        <span className="tooltip-value">{event.event_type}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Page:</span>
                        <span className="tooltip-value">{event.page}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Referrer:</span>
                        <span className="tooltip-value">{event.referrer || 'Direct'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Country:</span>
                        <span className="tooltip-value">{event.country || 'Unknown'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">OS:</span>
                        <span className="tooltip-value">{event.os || 'Unknown'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Browser:</span>
                        <span className="tooltip-value">{event.browser || 'Unknown'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Device Type:</span>
                        <span className="tooltip-value">{event.device_type || 'Unknown'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Resolution:</span>
                        <span className="tooltip-value">{event.resolution || 'Unknown'}</span>
                      </div>
                      <div className="tooltip-row">
                        <span className="tooltip-label">Timezone:</span>
                        <span className="tooltip-value">{event.timezone || 'Unknown'}</span>
                      </div>
                      {event.page_load && (
                        <div className="tooltip-row">
                          <span className="tooltip-label">Page Load:</span>
                          <span className="tooltip-value">{event.page_load}ms</span>
                        </div>
                      )}
                      <div className="tooltip-row">
                        <span className="tooltip-label">Is Bot:</span>
                        <span className="tooltip-value">{event.is_bot ? 'Yes' : 'No'}</span>
                      </div>
                    </Tooltip.Content>
                  </Tooltip.Portal>
                </Tooltip.Root>
              ))}
            </tbody>
          </table>
        </Tooltip.Provider>
      ) : (
        <div className="empty-state">
          <div className="empty-state-icon">👥</div>
          <div className="empty-state-text">No visitor events yet</div>
        </div>
      )}
    </div>
  );
}

export default LiveVisitorTable;
