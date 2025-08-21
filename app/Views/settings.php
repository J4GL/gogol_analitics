<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-tabs {
            display: flex;
            gap: 1rem;
        }
        
        .nav-tab {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #6c757d;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .nav-tab.active {
            background: #007bff;
            color: white;
        }
        
        .nav-tab:hover:not(.active) {
            background: #e9ecef;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
        }
        
        .card-description {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.4;
            overflow-x: auto;
            margin-bottom: 1rem;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 0.75rem;
            margin-top: 1rem;
            display: none;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            color: #6c757d;
            font-family: monospace;
        }
        
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .instructions h4 {
            color: #856404;
            margin-bottom: 0.5rem;
        }
        
        .instructions p {
            color: #856404;
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Traffic Analytics</h1>
        <nav class="nav-tabs">
            <a href="/" class="nav-tab <?= $currentTab === 'traffic' ? 'active' : '' ?>">Traffic</a>
            <a href="/settings" class="nav-tab <?= $currentTab === 'settings' ? 'active' : '' ?>">Settings</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Tracking Code</h2>
            <p class="card-description">
                Add this tracking code to your website's HTML, preferably in the &lt;head&gt; section or just before the closing &lt;/body&gt; tag.
            </p>
            
            <div class="instructions">
                <h4>Installation Instructions:</h4>
                <p>1. Copy the code snippet below<br>
                2. Paste it into your website's HTML template<br>
                3. The script will automatically start tracking page views</p>
            </div>
            
            <div class="code-block" id="tracking-code"><?= htmlspecialchars($trackingSnippet) ?></div>
            
            <div class="button-group">
                <button class="btn btn-primary" onclick="copyToClipboard()">Copy to Clipboard</button>
                <a href="<?= htmlspecialchars($domain) ?>/collect.js" class="btn btn-secondary" target="_blank">View Script</a>
            </div>
            
            <div class="success-message" id="success-message">
                ✓ Tracking code copied to clipboard!
            </div>
        </div>
        
        <div class="card">
            <h2 class="card-title">Configuration</h2>
            <p class="card-description">
                Current configuration settings for your analytics tracking.
            </p>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Domain</div>
                    <div class="info-value"><?= htmlspecialchars($domain) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Collection Endpoint</div>
                    <div class="info-value">/api/collect</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fallback Endpoint</div>
                    <div class="info-value">/collect.gif</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Data Retention</div>
                    <div class="info-value">7 days</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2 class="card-title">What We Track</h2>
            <p class="card-description">
                This analytics system collects the following information to provide insights about your website traffic:
            </p>
            
            <ul style="margin-left: 2rem; line-height: 1.6; color: #6c757d;">
                <li><strong>Page URL:</strong> The current page path and query parameters</li>
                <li><strong>Referrer:</strong> The page that linked to your site (if any)</li>
                <li><strong>Timezone:</strong> Visitor's timezone for geographic insights</li>
                <li><strong>Screen Resolution:</strong> Display dimensions for device analysis</li>
                <li><strong>Browser & OS:</strong> Browser and operating system information</li>
                <li><strong>Page Load Time:</strong> How long it took for the page to load</li>
                <li><strong>Bot Detection:</strong> Automatic filtering of bot traffic</li>
            </ul>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #e7f3ff; border-radius: 4px; border-left: 4px solid #007bff;">
                <strong>Privacy Note:</strong> We do not store IP addresses directly. Instead, we generate anonymous visitor IDs using a hash of the IP and user agent, ensuring visitor privacy while maintaining accurate analytics.
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard() {
            const codeBlock = document.getElementById('tracking-code');
            const text = codeBlock.textContent;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showSuccessMessage();
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        }
        
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showSuccessMessage();
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                alert('Failed to copy to clipboard. Please copy the code manually.');
            }
            
            document.body.removeChild(textArea);
        }
        
        function showSuccessMessage() {
            const message = document.getElementById('success-message');
            message.style.display = 'block';
            
            setTimeout(function() {
                message.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
