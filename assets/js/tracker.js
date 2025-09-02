(function() {
    'use strict';
    
    // Configuration
    const GOGOL_CONFIG = {
        apiUrl: window.GOGOL_API_URL || '/api/collect.php',
        debug: false
    };
    
    // Bot detection patterns
    const BOT_PATTERNS = [
        /googlebot/i, /bingbot/i, /slurp/i, /duckduckbot/i, /baiduspider/i,
        /yandexbot/i, /sogou/i, /facebookexternalhit/i, /twitterbot/i,
        /linkedinbot/i, /whatsapp/i, /telegrambot/i, /applebot/i,
        /crawler/i, /spider/i, /scraper/i, /bot/i, /archiver/i
    ];
    
    // Utility functions
    function generateHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(36);
    }
    
    function getIPFingerprint() {
        // Since we can't get IP on client-side, we'll use other unique identifiers
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Gogol Analytics', 2, 2);
        
        return generateHash(
            navigator.userAgent +
            navigator.language +
            screen.width + 'x' + screen.height +
            new Date().getTimezoneOffset() +
            (canvas.toDataURL ? canvas.toDataURL() : '')
        );
    }
    
    function detectOS() {
        const userAgent = navigator.userAgent;
        if (userAgent.indexOf('Windows') !== -1) return 'Windows';
        if (userAgent.indexOf('Mac') !== -1) return 'macOS';
        if (userAgent.indexOf('Linux') !== -1) return 'Linux';
        if (userAgent.indexOf('Android') !== -1) return 'Android';
        if (userAgent.indexOf('iOS') !== -1) return 'iOS';
        return 'Unknown';
    }
    
    function detectBrowser() {
        const userAgent = navigator.userAgent;
        if (userAgent.indexOf('Chrome') !== -1 && userAgent.indexOf('Edge') === -1) return 'Chrome';
        if (userAgent.indexOf('Firefox') !== -1) return 'Firefox';
        if (userAgent.indexOf('Safari') !== -1 && userAgent.indexOf('Chrome') === -1) return 'Safari';
        if (userAgent.indexOf('Edge') !== -1) return 'Edge';
        if (userAgent.indexOf('Opera') !== -1) return 'Opera';
        return 'Unknown';
    }
    
    function detectDeviceType() {
        return /Mobi|Android/i.test(navigator.userAgent) ? 'MOBILE' : 'PC';
    }
    
    function isBot() {
        const userAgent = navigator.userAgent;
        return BOT_PATTERNS.some(pattern => pattern.test(userAgent));
    }
    
    function getCountryFromLocale() {
        try {
            // Try to get country from navigator.language (e.g., "en-US" -> "US")
            const locale = navigator.language || navigator.languages[0];
            const parts = locale.split('-');
            if (parts.length > 1) {
                return parts[1].toUpperCase();
            }
            
            // Fallback to timezone-based detection
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const countryMap = {
                'America/New_York': 'US', 'America/Chicago': 'US', 'America/Denver': 'US', 'America/Los_Angeles': 'US',
                'Europe/London': 'GB', 'Europe/Paris': 'FR', 'Europe/Berlin': 'DE', 'Europe/Rome': 'IT',
                'Europe/Madrid': 'ES', 'Asia/Tokyo': 'JP', 'Asia/Shanghai': 'CN', 'Asia/Kolkata': 'IN',
                'Australia/Sydney': 'AU', 'America/Toronto': 'CA', 'America/Sao_Paulo': 'BR'
            };
            
            return countryMap[timezone] || 'XX';
        } catch (e) {
            return 'XX';
        }
    }
    
    function measurePageLoadTime() {
        try {
            if (performance && performance.timing) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                return loadTime > 0 ? loadTime : null;
            }
        } catch (e) {
            // Fallback: measure from script execution
            return Date.now() - window.GOGOL_START_TIME;
        }
        return null;
    }
    
    // Main tracking class
    class GogolTracker {
        constructor() {
            this.visitorId = this.generateVisitorId();
            this.sessionId = this.generateSessionId();
            this.startTime = Date.now();
            
            if (GOGOL_CONFIG.debug) {
                console.log('Gogol Analytics initialized', {
                    visitorId: this.visitorId,
                    sessionId: this.sessionId
                });
            }
        }
        
        generateVisitorId() {
            // Create a persistent visitor ID based on browser fingerprint
            let stored = this.getStoredId('gogol_visitor_id');
            if (!stored) {
                stored = getIPFingerprint();
                this.setStoredId('gogol_visitor_id', stored);
            }
            return stored;
        }
        
        generateSessionId() {
            // Generate session ID that expires after 30 minutes of inactivity
            const sessionKey = 'gogol_session_id';
            const timestampKey = 'gogol_session_timestamp';
            const sessionTimeout = 30 * 60 * 1000; // 30 minutes
            
            const now = Date.now();
            const storedTime = localStorage.getItem(timestampKey);
            const storedSession = localStorage.getItem(sessionKey);
            
            if (storedSession && storedTime && (now - parseInt(storedTime)) < sessionTimeout) {
                localStorage.setItem(timestampKey, now.toString());
                return storedSession;
            }
            
            // Generate new session
            const newSession = generateHash(now.toString() + Math.random().toString());
            localStorage.setItem(sessionKey, newSession);
            localStorage.setItem(timestampKey, now.toString());
            return newSession;
        }
        
        getStoredId(key) {
            try {
                return localStorage.getItem(key) || sessionStorage.getItem(key);
            } catch (e) {
                return null;
            }
        }
        
        setStoredId(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (e) {
                try {
                    sessionStorage.setItem(key, value);
                } catch (e) {
                    // Storage not available
                }
            }
        }
        
        collectEventData(eventType = 'pageview', customData = {}) {
            return {
                timestamp: Date.now(),
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                event_type: eventType,
                page: window.location.href,
                referrer: document.referrer || '',
                country: getCountryFromLocale(),
                os: detectOS(),
                browser: detectBrowser(),
                device_type: detectDeviceType(),
                resolution: screen.width + 'x' + screen.height,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                page_load_time: measurePageLoadTime(),
                is_bot: isBot(),
                user_agent: navigator.userAgent,
                ...customData
            };
        }
        
        sendEvent(eventType, customData = {}) {
            const data = this.collectEventData(eventType, customData);
            
            if (GOGOL_CONFIG.debug) {
                console.log('Sending event:', data);
            }
            
            // Send via fetch with success confirmation
            if (fetch) {
                fetch(GOGOL_CONFIG.apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        if (eventType === 'click') {
                            console.log('✅ Click event sent successfully');
                        }
                        if (GOGOL_CONFIG.debug) {
                            console.log('API Response:', result);
                        }
                    }
                }).catch(e => {
                    if (GOGOL_CONFIG.debug) console.error('Tracking failed:', e);
                });
            } else if (navigator.sendBeacon) {
                navigator.sendBeacon(GOGOL_CONFIG.apiUrl, JSON.stringify(data));
                if (eventType === 'click') {
                    console.log('✅ Click event sent via beacon');
                }
            } else {
                // Fallback to image pixel
                const img = new Image();
                img.src = GOGOL_CONFIG.apiUrl + '?' + Object.keys(data)
                    .map(k => k + '=' + encodeURIComponent(data[k]))
                    .join('&');
                if (eventType === 'click') {
                    console.log('✅ Click event sent via image pixel');
                }
            }
        }
        
        trackPageview() {
            this.sendEvent('pageview');
        }
        
        trackEvent(eventName, properties = {}) {
            this.sendEvent(eventName, properties);
        }
        
        setupClickTracking() {
            // Track clicks on various UI elements
            document.addEventListener('click', (event) => {
                const element = event.target;
                const tagName = element.tagName.toLowerCase();
                let elementData = {
                    tag_name: tagName,
                    element_id: element.id || null,
                    element_class: element.className || null,
                    element_text: null,
                    element_type: null,
                    element_href: null
                };
                
                // Extract text and specific attributes based on element type
                switch (tagName) {
                    case 'a':
                        elementData.element_text = element.textContent?.trim() || element.innerText?.trim() || null;
                        elementData.element_href = element.href || null;
                        break;
                        
                    case 'button':
                        elementData.element_text = element.textContent?.trim() || element.innerText?.trim() || element.value || null;
                        elementData.element_type = element.type || 'button';
                        break;
                        
                    case 'input':
                        elementData.element_type = element.type || 'text';
                        if (element.type === 'submit') {
                            elementData.element_text = element.value || null;
                        } else if (element.type === 'checkbox' || element.type === 'radio') {
                            // Get associated label text
                            const label = document.querySelector(`label[for="${element.id}"]`) || element.closest('label');
                            elementData.element_text = label ? (label.textContent?.trim() || label.innerText?.trim()) : null;
                        }
                        break;
                        
                    case 'select':
                        const selectedOption = element.options[element.selectedIndex];
                        elementData.element_text = selectedOption ? selectedOption.text : null;
                        break;
                        
                    case 'textarea':
                        // Don't capture actual content for privacy
                        elementData.element_text = 'textarea';
                        break;
                        
                    default:
                        // For other clickable elements, try to get text content
                        if (element.onclick || element.addEventListener || element.getAttribute('onclick')) {
                            elementData.element_text = element.textContent?.trim() || element.innerText?.trim() || null;
                        }
                        break;
                }
                
                // Only track if we have meaningful data
                if (elementData.element_text || elementData.element_href || elementData.element_id || 
                    ['button', 'input', 'select', 'a'].includes(tagName)) {
                    this.sendEvent('click', elementData);
                    
                    // Always log successful click tracking
                    console.log('✅ Click tracked:', elementData.element_text || elementData.element_tag_name || tagName);
                    
                    if (GOGOL_CONFIG.debug) {
                        console.log('Full click data:', elementData);
                    }
                }
            });
        }
    }
    
    // Initialize tracker
    window.GOGOL_START_TIME = Date.now();
    
    // Create global tracker instance
    window.gogol = new GogolTracker();
    
    // Auto-track pageview and setup click tracking when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.gogol.trackPageview();
            window.gogol.setupClickTracking();
        });
    } else {
        window.gogol.trackPageview();
        window.gogol.setupClickTracking();
    }
    
    // Track page visibility changes
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            window.gogol.trackEvent('page_visible');
        } else {
            window.gogol.trackEvent('page_hidden');
        }
    });
    
    // Track page unload
    window.addEventListener('beforeunload', () => {
        window.gogol.trackEvent('page_unload');
    });
    
})();