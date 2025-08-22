/**
 * Gogol Analytics Tracking Script
 * Tracks visitor behavior and sends data to the analytics server
 */

(function() {
    'use strict';
    
    class GogolTracker {
        constructor() {
            this.config = {
                apiUrl: '',
                siteUrl: '',
                visitorUuid: null,
                sessionId: null,
                debug: false,
                trackingEnabled: true,
                eventQueue: [],
                maxRetries: 3,
                retryDelay: 1000,
                batchSize: 10,
                flushInterval: 5000
            };
            
            this.eventTypes = {
                PAGEVIEW: 'pageview',
                CLICK: 'click',
                SCROLL: 'scroll',
                CUSTOM: 'custom'
            };
            
            this.sessionTimeout = 30 * 60 * 1000; // 30 minutes
            this.scrollThreshold = 0.25; // 25% scroll threshold
            this.scrollTracked = false;
            
            this.init();
        }
        
        init() {
            // Check if tracking is disabled
            if (this.isDoNotTrack()) {
                this.log('Tracking disabled due to Do Not Track setting');
                return;
            }
            
            this.loadConfig();
            this.initializeVisitor();
            this.setupEventListeners();
            this.trackPageView();
            this.startBatchProcessing();
            
            this.log('Gogol Analytics initialized', this.config);
        }
        
        loadConfig() {
            const script = document.currentScript || this.findScript();
            
            if (script) {
                this.config.apiUrl = script.getAttribute('data-api-url') || '/api/track.php';
                this.config.siteUrl = script.getAttribute('data-site-url') || window.location.origin;
                this.config.debug = script.getAttribute('data-debug') === 'true';
            }
            
            // Validate required config
            if (!this.config.apiUrl) {
                console.error('Gogol Analytics: API URL not configured');
                this.config.trackingEnabled = false;
                return;
            }
        }
        
        findScript() {
            const scripts = document.getElementsByTagName('script');
            for (let script of scripts) {
                if (script.src && script.src.includes('tracker.js')) {
                    return script;
                }
            }
            return null;
        }
        
        initializeVisitor() {
            // Get or create visitor UUID
            this.config.visitorUuid = this.getOrCreateVisitorUuid();
            
            // Generate session ID
            this.config.sessionId = this.generateSessionId();
            
            this.log('Visitor initialized', {
                visitorUuid: this.config.visitorUuid,
                sessionId: this.config.sessionId
            });
        }
        
        getOrCreateVisitorUuid() {
            const storageKey = 'gogol_visitor_uuid';
            let visitorUuid = this.getFromStorage(storageKey);
            
            if (!visitorUuid || !this.isValidUuid(visitorUuid)) {
                visitorUuid = this.generateUuid();
                this.setInStorage(storageKey, visitorUuid);
                this.log('New visitor UUID generated:', visitorUuid);
            } else {
                this.log('Existing visitor UUID found:', visitorUuid);
            }
            
            return visitorUuid;
        }
        
        generateSessionId() {
            const sessionKey = 'gogol_session_id';
            const sessionTimestampKey = 'gogol_session_timestamp';
            
            const now = Date.now();
            const sessionTimestamp = this.getFromStorage(sessionTimestampKey);
            let sessionId = this.getFromStorage(sessionKey);
            
            // Check if session has expired
            if (!sessionId || !sessionTimestamp || (now - parseInt(sessionTimestamp)) > this.sessionTimeout) {
                sessionId = this.generateId(16);
                this.setInStorage(sessionKey, sessionId);
                this.log('New session created:', sessionId);
            }
            
            // Update session timestamp
            this.setInStorage(sessionTimestampKey, now.toString());
            
            return sessionId;
        }
        
        setupEventListeners() {
            // Page visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.flushEventQueue();
                }
            });
            
            // Page unload
            window.addEventListener('beforeunload', () => {
                this.flushEventQueue();
            });
            
            // Click tracking
            document.addEventListener('click', (event) => {
                this.trackClick(event);
            }, true);
            
            // Scroll tracking
            window.addEventListener('scroll', this.throttle(() => {
                this.trackScroll();
            }, 1000));
            
            // Hash change (SPA navigation)
            window.addEventListener('hashchange', () => {
                this.trackPageView();
            });
            
            // History API changes (SPA navigation)
            this.interceptHistoryMethods();
        }
        
        interceptHistoryMethods() {
            const originalPushState = history.pushState;
            const originalReplaceState = history.replaceState;
            
            history.pushState = (...args) => {
                originalPushState.apply(history, args);
                setTimeout(() => this.trackPageView(), 0);
            };
            
            history.replaceState = (...args) => {
                originalReplaceState.apply(history, args);
                setTimeout(() => this.trackPageView(), 0);
            };
            
            window.addEventListener('popstate', () => {
                setTimeout(() => this.trackPageView(), 0);
            });
        }
        
        // Public tracking methods
        trackPageView(customData = {}) {
            if (!this.config.trackingEnabled) return;
            
            const eventData = {
                event_type: this.eventTypes.PAGEVIEW,
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer || null,
                timestamp: Date.now(),
                event_data: {
                    user_agent: navigator.userAgent,
                    screen_resolution: `${screen.width}x${screen.height}`,
                    viewport_size: `${window.innerWidth}x${window.innerHeight}`,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    language: navigator.language,
                    platform: navigator.platform,
                    cookie_enabled: navigator.cookieEnabled,
                    ...customData
                }
            };
            
            this.queueEvent(eventData);
            this.scrollTracked = false; // Reset scroll tracking for new page
            
            this.log('Page view tracked', eventData);
        }
        
        trackClick(event) {
            if (!this.config.trackingEnabled) return;
            
            const element = event.target;
            const elementInfo = this.getElementInfo(element);
            
            // Only track meaningful clicks
            if (!this.shouldTrackClick(element)) {
                return;
            }
            
            const eventData = {
                event_type: this.eventTypes.CLICK,
                page_url: window.location.href,
                timestamp: Date.now(),
                event_data: {
                    element_tag: element.tagName.toLowerCase(),
                    element_id: element.id || null,
                    element_class: element.className || null,
                    element_text: elementInfo.text,
                    element_href: elementInfo.href,
                    click_x: event.clientX,
                    click_y: event.clientY
                }
            };
            
            this.queueEvent(eventData);
            this.log('Click tracked', eventData);
        }
        
        trackScroll() {
            if (!this.config.trackingEnabled || this.scrollTracked) return;
            
            const scrollPercent = this.getScrollPercent();
            
            if (scrollPercent >= this.scrollThreshold) {
                const eventData = {
                    event_type: this.eventTypes.SCROLL,
                    page_url: window.location.href,
                    timestamp: Date.now(),
                    event_data: {
                        scroll_percent: scrollPercent,
                        scroll_depth: window.pageYOffset,
                        page_height: document.documentElement.scrollHeight,
                        viewport_height: window.innerHeight
                    }
                };
                
                this.queueEvent(eventData);
                this.scrollTracked = true;
                
                this.log('Scroll tracked', eventData);
            }
        }
        
        trackCustomEvent(eventName, eventData = {}) {
            if (!this.config.trackingEnabled) return;
            
            const customEventData = {
                event_type: this.eventTypes.CUSTOM,
                page_url: window.location.href,
                timestamp: Date.now(),
                event_data: {
                    custom_event_name: eventName,
                    ...eventData
                }
            };
            
            this.queueEvent(customEventData);
            this.log('Custom event tracked', customEventData);
        }
        
        // Event queue management
        queueEvent(eventData) {
            const completeEventData = {
                visitor_uuid: this.config.visitorUuid,
                session_id: this.config.sessionId,
                ...eventData
            };
            
            this.config.eventQueue.push(completeEventData);
            
            if (this.config.eventQueue.length >= this.config.batchSize) {
                this.flushEventQueue();
            }
        }
        
        async flushEventQueue() {
            if (this.config.eventQueue.length === 0) return;
            
            const eventsToSend = [...this.config.eventQueue];
            this.config.eventQueue = [];
            
            // Send events individually for better error handling
            for (const eventData of eventsToSend) {
                await this.sendEvent(eventData);
            }
        }
        
        async sendEvent(eventData, retryCount = 0) {
            try {
                const response = await fetch(this.config.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(eventData),
                    credentials: 'omit'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.status !== 'success') {
                    throw new Error(result.message || 'Unknown error');
                }
                
                this.log('Event sent successfully', result);
                
            } catch (error) {
                this.log('Error sending event:', error);
                
                // Retry logic
                if (retryCount < this.config.maxRetries) {
                    setTimeout(() => {
                        this.sendEvent(eventData, retryCount + 1);
                    }, this.config.retryDelay * Math.pow(2, retryCount)); // Exponential backoff
                } else {
                    this.log('Failed to send event after max retries', eventData);
                }
            }
        }
        
        startBatchProcessing() {
            setInterval(() => {
                this.flushEventQueue();
            }, this.config.flushInterval);
        }
        
        // Utility methods
        generateUuid() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        
        generateId(length = 8) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
        
        isValidUuid(uuid) {
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return uuidRegex.test(uuid);
        }
        
        getFromStorage(key) {
            try {
                return localStorage.getItem(key);
            } catch (e) {
                this.log('LocalStorage not available, using sessionStorage');
                try {
                    return sessionStorage.getItem(key);
                } catch (e) {
                    this.log('SessionStorage not available, using in-memory storage');
                    return this.memoryStorage?.[key] || null;
                }
            }
        }
        
        setInStorage(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (e) {
                try {
                    sessionStorage.setItem(key, value);
                } catch (e) {
                    this.memoryStorage = this.memoryStorage || {};
                    this.memoryStorage[key] = value;
                }
            }
        }
        
        isDoNotTrack() {
            return navigator.doNotTrack === '1' || 
                   window.doNotTrack === '1' || 
                   navigator.msDoNotTrack === '1';
        }
        
        shouldTrackClick(element) {
            // Don't track clicks on certain elements
            const excludeTags = ['script', 'style', 'meta', 'title'];
            const excludeClasses = ['no-track', 'tracker-ignore'];
            
            if (excludeTags.includes(element.tagName.toLowerCase())) {
                return false;
            }
            
            if (element.className && excludeClasses.some(cls => 
                element.className.split(' ').includes(cls))) {
                return false;
            }
            
            return true;
        }
        
        getElementInfo(element) {
            const text = element.textContent?.trim().substring(0, 100) || '';
            const href = element.href || element.closest('a')?.href || null;
            
            return { text, href };
        }
        
        getScrollPercent() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
            
            if (documentHeight <= 0) return 0;
            
            return Math.min(scrollTop / documentHeight, 1);
        }
        
        throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
        
        log(...args) {
            if (this.config.debug) {
                console.log('[Gogol Analytics]', ...args);
            }
        }
        
        // Public API
        getVisitorUuid() {
            return this.config.visitorUuid;
        }
        
        getSessionId() {
            return this.config.sessionId;
        }
        
        setConfig(key, value) {
            if (this.config.hasOwnProperty(key)) {
                this.config[key] = value;
            }
        }
        
        disable() {
            this.config.trackingEnabled = false;
            this.log('Tracking disabled');
        }
        
        enable() {
            this.config.trackingEnabled = true;
            this.log('Tracking enabled');
        }
    }
    
    // Initialize tracker
    let tracker;
    
    function initTracker() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                tracker = new GogolTracker();
            });
        } else {
            tracker = new GogolTracker();
        }
    }
    
    // Expose public API
    window.gogolAnalytics = {
        track: (eventName, eventData) => {
            if (tracker) {
                tracker.trackCustomEvent(eventName, eventData);
            }
        },
        getVisitorUuid: () => tracker?.getVisitorUuid(),
        getSessionId: () => tracker?.getSessionId(),
        disable: () => tracker?.disable(),
        enable: () => tracker?.enable(),
        setConfig: (key, value) => tracker?.setConfig(key, value)
    };
    
    // Start tracking
    initTracker();
    
})();