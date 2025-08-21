(function() {
    'use strict';
    
    // Configuration
    var DOMAIN = 'https://domaine.com'; // Will be replaced with actual domain
    var ENDPOINT = '/api/collect';
    
    // Utility functions
    function toBase64Unicode(str) {
        try {
            return btoa(unescape(encodeURIComponent(str)));
        } catch (e) {
            console.warn('Base64 encoding failed:', e);
            return '';
        }
    }
    
    function detectOS(userAgent) {
        var ua = userAgent.toLowerCase();
        
        if (ua.indexOf('windows nt 10') !== -1) return 'Windows 10';
        if (ua.indexOf('windows nt 6.3') !== -1) return 'Windows 8.1';
        if (ua.indexOf('windows nt 6.2') !== -1) return 'Windows 8';
        if (ua.indexOf('windows nt 6.1') !== -1) return 'Windows 7';
        if (ua.indexOf('windows nt 6.0') !== -1) return 'Windows Vista';
        if (ua.indexOf('windows nt 5.1') !== -1) return 'Windows XP';
        if (ua.indexOf('windows') !== -1) return 'Windows';
        
        if (ua.indexOf('mac os x 10_15') !== -1) return 'macOS Catalina';
        if (ua.indexOf('mac os x 10_14') !== -1) return 'macOS Mojave';
        if (ua.indexOf('mac os x 10_13') !== -1) return 'macOS High Sierra';
        if (ua.indexOf('mac os x') !== -1) return 'macOS';
        if (ua.indexOf('macintosh') !== -1) return 'macOS';
        
        if (ua.indexOf('iphone') !== -1) return 'iOS';
        if (ua.indexOf('ipad') !== -1) return 'iOS';
        if (ua.indexOf('android') !== -1) return 'Android';
        
        if (ua.indexOf('ubuntu') !== -1) return 'Ubuntu';
        if (ua.indexOf('debian') !== -1) return 'Debian';
        if (ua.indexOf('fedora') !== -1) return 'Fedora';
        if (ua.indexOf('centos') !== -1) return 'CentOS';
        if (ua.indexOf('linux') !== -1) return 'Linux';
        
        if (ua.indexOf('freebsd') !== -1) return 'FreeBSD';
        if (ua.indexOf('openbsd') !== -1) return 'OpenBSD';
        if (ua.indexOf('netbsd') !== -1) return 'NetBSD';
        
        return 'Unknown';
    }
    
    function detectBrowser(userAgent) {
        var ua = userAgent.toLowerCase();
        
        if (ua.indexOf('edg/') !== -1) return 'Edge';
        if (ua.indexOf('chrome/') !== -1 && ua.indexOf('edg/') === -1) return 'Chrome';
        if (ua.indexOf('firefox/') !== -1) return 'Firefox';
        if (ua.indexOf('safari/') !== -1 && ua.indexOf('chrome/') === -1) return 'Safari';
        if (ua.indexOf('opera/') !== -1 || ua.indexOf('opr/') !== -1) return 'Opera';
        if (ua.indexOf('msie') !== -1 || ua.indexOf('trident/') !== -1) return 'Internet Explorer';
        
        return 'Unknown';
    }
    
    function getTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            return null;
        }
    }
    
    function getResolution() {
        try {
            return screen.width + 'x' + screen.height;
        } catch (e) {
            return null;
        }
    }
    
    function getPageLoadTime() {
        try {
            var pageStartTime = window.pageStartTime || 0;
            var navigationStart = 0;
            
            if (window.performance && window.performance.timing) {
                navigationStart = window.performance.timing.navigationStart;
            }
            
            var startTime = pageStartTime || navigationStart || 0;
            var currentTime = window.performance ? window.performance.now() : Date.now();
            
            return Math.round(currentTime - startTime);
        } catch (e) {
            return null;
        }
    }
    
    function getClientTimestamp() {
        try {
            var timeOrigin = window.performance && window.performance.timeOrigin 
                ? window.performance.timeOrigin 
                : Date.now();
            var now = window.performance && window.performance.now 
                ? window.performance.now() 
                : 0;
            
            return Math.floor((timeOrigin + now) * 1000000); // Convert to nanoseconds
        } catch (e) {
            return Math.floor(Date.now() * 1000000); // Fallback to milliseconds * 1000
        }
    }
    
    function sendEvent(eventData) {
        try {
            var payload = {
                page: location.pathname + location.search,
                referrer: document.referrer || null,
                timezone: getTimezone(),
                resolution: getResolution(),
                ua: navigator.userAgent,
                webdriver: !!navigator.webdriver,
                os: detectOS(navigator.userAgent),
                browser: detectBrowser(navigator.userAgent),
                page_load_ms: getPageLoadTime(),
                client_ts_ns: getClientTimestamp()
            };
            
            // Merge any additional event data
            if (eventData) {
                for (var key in eventData) {
                    if (eventData.hasOwnProperty(key)) {
                        payload[key] = eventData[key];
                    }
                }
            }
            
            var jsonString = JSON.stringify(payload);
            var b64 = toBase64Unicode(jsonString);
            
            if (!b64) {
                console.warn('Failed to encode payload');
                return;
            }
            
            // Send via image request to avoid CORS issues
            var img = new Image();
            var completed = false;

            // Set up timeout to prevent hanging
            var timeout = setTimeout(function() {
                if (!completed) {
                    completed = true;
                    console.warn('Analytics request timed out');
                    img = null;
                }
            }, 5000); // 5 second timeout

            img.onload = img.onerror = function() {
                if (!completed) {
                    completed = true;
                    clearTimeout(timeout);
                    img = null;
                }
            };

            var url = DOMAIN + ENDPOINT + '?data=' + encodeURIComponent(b64);

            // Check URL length to avoid server limits
            if (url.length > 2000) {
                console.warn('URL too long, truncating payload');
                // Try with minimal payload
                var minimalPayload = {
                    page: payload.page,
                    timezone: payload.timezone,
                    client_ts_ns: payload.client_ts_ns
                };
                b64 = toBase64Unicode(JSON.stringify(minimalPayload));
                url = DOMAIN + ENDPOINT + '?data=' + encodeURIComponent(b64);
            }

            img.src = url;
            
        } catch (e) {
            console.warn('Analytics error:', e);
        }
    }
    
    // Auto-track page load event
    function trackPageLoad() {
        if (document.readyState === 'complete') {
            sendEvent({ event_type: 'page_load' });
        } else {
            window.addEventListener('load', function() {
                sendEvent({ event_type: 'page_load' });
            });
        }
    }

    // Auto-track link clicks
    function trackLinkClicks() {
        document.addEventListener('click', function(event) {
            var target = event.target;

            // Find the closest link element (in case user clicked on text inside a link)
            while (target && target.tagName !== 'A') {
                target = target.parentElement;
                if (!target || target === document.body) {
                    return; // Not a link click
                }
            }

            if (target && target.tagName === 'A' && target.href) {
                var linkUrl = target.href;
                var linkText = target.textContent || target.innerText || '';

                // Track the link click
                sendEvent({
                    event_type: 'link_click',
                    link_url: linkUrl,
                    link_text: linkText.trim().substring(0, 100) // Limit text length
                });
            }
        });
    }

    // Public API
    window.analytics = {
        track: sendEvent,
        trackPageLoad: trackPageLoad,
        trackLinkClicks: trackLinkClicks
    };

    // Auto-initialize
    trackPageLoad();
    trackLinkClicks();
    
})();
