import { useState } from 'react';

function SettingsTab() {
  const [copied, setCopied] = useState(false);

  const scriptSnippet = `<script src="${window.location.origin}/track.js"></script>
<script>
  if (window.initAnalytics) {
    window.initAnalytics({ endpoint: '${window.location.origin}/api/events' });
  }
</script>`;

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(scriptSnippet);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Failed to copy:', error);
    }
  };

  return (
    <div className="settings-container">
      <h2 className="settings-title">Tracking Script</h2>
      <p className="settings-description">
        Copy and paste this script into your website's &lt;head&gt; section to start tracking visitors:
      </p>
      
      <div className="code-snippet">
        {scriptSnippet}
      </div>
      
      <button 
        className={`copy-button ${copied ? 'copied' : ''}`}
        onClick={handleCopy}
      >
        {copied ? '✓ Copied!' : 'Copy to Clipboard'}
      </button>

      <div style={{ marginTop: '32px', paddingTop: '32px', borderTop: '1px solid #e0e0e0' }}>
        <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '12px', color: '#333' }}>
          What data is collected?
        </h3>
        <ul style={{ paddingLeft: '20px', color: '#666', lineHeight: '1.8' }}>
          <li>Timestamp (when the event occurred)</li>
          <li>Page URL and referrer</li>
          <li>Event type (pageview, button click, link click)</li>
          <li>Country (from browser locale)</li>
          <li>Operating system and browser</li>
          <li>Device type (PC or Mobile)</li>
          <li>Screen resolution</li>
          <li>Timezone</li>
          <li>Page load time</li>
          <li>Bot detection status</li>
        </ul>
      </div>
    </div>
  );
}

export default SettingsTab;
