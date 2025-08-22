/**
 * Demo Website JavaScript
 * Handles interactive functionality and demonstrates tracking capabilities
 */

class DemoWebsite {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.updateVisitorInfo();
        this.setupFormHandling();
        this.addDemoFunctionality();
    }
    
    setupEventListeners() {
        // Track all button clicks with data-track attribute
        document.addEventListener('click', (event) => {
            const element = event.target;
            const trackData = element.getAttribute('data-track');
            
            if (trackData) {
                this.handleTrackedClick(element, trackData);
            }
        });
        
        // Track form interactions
        document.addEventListener('change', (event) => {
            const element = event.target;
            if (element.tagName === 'SELECT' || element.tagName === 'INPUT') {
                this.trackFormInteraction(element);
            }
        });
        
        // Track scroll to bottom
        window.addEventListener('scroll', this.throttle(() => {
            this.checkScrollToBottom();
        }, 1000));
    }
    
    handleTrackedClick(element, trackData) {
        const eventData = {
            track_type: trackData,
            element_id: element.id || null,
            element_class: element.className || null,
            element_text: element.textContent?.trim().substring(0, 50) || null,
            page_url: window.location.href
        };
        
        // Add any additional data attributes
        Array.from(element.attributes).forEach(attr => {
            if (attr.name.startsWith('data-') && attr.name !== 'data-track') {
                eventData[attr.name.replace('data-', '')] = attr.value;
            }
        });
        
        this.trackCustomEvent('demo_interaction', eventData);
        
        console.log('Demo: Tracked click event', {
            trackData,
            eventData
        });
    }
    
    trackFormInteraction(element) {
        const formData = {
            form_id: element.form?.id || null,
            field_name: element.name || null,
            field_type: element.type || element.tagName.toLowerCase(),
            field_value: element.type === 'password' ? '[HIDDEN]' : element.value?.substring(0, 50) || null
        };
        
        this.trackCustomEvent('form_interaction', formData);
        
        console.log('Demo: Tracked form interaction', formData);
    }
    
    checkScrollToBottom() {
        const scrollPercent = (window.pageYOffset + window.innerHeight) / document.documentElement.scrollHeight;
        
        if (scrollPercent > 0.95) {
            this.trackCustomEvent('scroll_to_bottom', {
                page_url: window.location.href,
                page_title: document.title
            });
        }
    }
    
    updateVisitorInfo() {
        // Wait for tracking script to be available
        setTimeout(() => {
            if (window.gogolAnalytics) {
                const visitorUuid = window.gogolAnalytics.getVisitorUuid();
                const sessionId = window.gogolAnalytics.getSessionId();
                
                // Update visitor info display
                const visitorUuidEl = document.getElementById('visitor-uuid');
                const sessionIdEl = document.getElementById('session-id');
                const pageLoadTimeEl = document.getElementById('page-load-time');
                const screenResolutionEl = document.getElementById('screen-resolution');
                
                if (visitorUuidEl) {
                    visitorUuidEl.textContent = visitorUuid ? visitorUuid.substring(0, 8) + '...' : 'Loading...';
                }
                
                if (sessionIdEl) {
                    sessionIdEl.textContent = sessionId || 'Loading...';
                }
                
                if (pageLoadTimeEl) {
                    pageLoadTimeEl.textContent = new Date().toLocaleTimeString();
                }
                
                if (screenResolutionEl) {
                    screenResolutionEl.textContent = `${screen.width}x${screen.height}`;
                }
            } else {
                console.log('Demo: Tracking script not yet available, retrying...');
                setTimeout(() => this.updateVisitorInfo(), 1000);
            }
        }, 500);
    }
    
    setupFormHandling() {
        const contactForm = document.getElementById('contact-form');
        
        if (contactForm) {
            contactForm.addEventListener('submit', (event) => {
                event.preventDefault();
                this.handleFormSubmission(contactForm);
            });
        }
    }
    
    handleFormSubmission(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Track form submission
        this.trackCustomEvent('form_submission', {
            form_id: form.id,
            subject: data.subject || 'unknown',
            has_company: !!data.company,
            newsletter_signup: !!data.newsletter,
            form_fields_count: Object.keys(data).length
        });
        
        // Show success message
        this.showFormSuccessMessage(form);
        
        console.log('Demo: Form submitted', {
            formId: form.id,
            dataKeys: Object.keys(data)
        });
    }
    
    showFormSuccessMessage(form) {
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
        successAlert.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <strong>Success!</strong> Your message has been sent. We'll get back to you soon.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        form.parentNode.insertBefore(successAlert, form.nextSibling);
        
        // Reset form
        form.reset();
        
        // Remove success message after 5 seconds
        setTimeout(() => {
            if (successAlert.parentNode) {
                successAlert.parentNode.removeChild(successAlert);
            }
        }, 5000);
    }
    
    addDemoFunctionality() {
        // Add demo-specific functions to global scope
        window.trackCustomEvent = (eventName, eventData) => {
            if (window.gogolAnalytics) {
                window.gogolAnalytics.track(eventName, eventData);
                console.log('Demo: Custom event tracked', { eventName, eventData });
            }
        };
        
        window.scrollToBottom = () => {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
            
            this.trackCustomEvent('manual_scroll_to_bottom', {
                triggered_by: 'button_click',
                page_url: window.location.href
            });
        };
        
        window.refreshVisitorInfo = () => {
            this.updateVisitorInfo();
            this.trackCustomEvent('refresh_visitor_info', {
                page_url: window.location.href
            });
        };
        
        window.startLiveChat = () => {
            this.trackCustomEvent('live_chat_initiated', {
                page_url: window.location.href,
                page_title: document.title
            });
            
            alert('Demo: Live chat would normally open here. This action has been tracked!');
        };
    }
    
    trackCustomEvent(eventName, eventData) {
        if (window.gogolAnalytics) {
            window.gogolAnalytics.track(eventName, eventData);
        } else {
            console.log('Demo: Tracking not available yet, queuing event', { eventName, eventData });
            
            // Queue event for when tracking becomes available
            setTimeout(() => {
                if (window.gogolAnalytics) {
                    window.gogolAnalytics.track(eventName, eventData);
                }
            }, 1000);
        }
    }
    
    // Utility function for throttling
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
    
    // Generate some demo activity
    simulateDemoActivity() {
        const demoEvents = [
            { name: 'demo_page_engagement', data: { section: 'hero', action: 'view' }},
            { name: 'demo_feature_interest', data: { feature: 'real_time_analytics' }},
            { name: 'demo_navigation', data: { from: 'home', to: 'about' }}
        ];
        
        let eventIndex = 0;
        const interval = setInterval(() => {
            if (eventIndex >= demoEvents.length) {
                clearInterval(interval);
                return;
            }
            
            const event = demoEvents[eventIndex];
            this.trackCustomEvent(event.name, event.data);
            eventIndex++;
        }, 2000);
    }
    
    // Show demo notifications
    showDemoNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed demo-notification`;
        notification.style.cssText = `
            top: 20px; 
            right: 20px; 
            z-index: 9999; 
            min-width: 300px;
            max-width: 400px;
        `;
        notification.innerHTML = `
            <i class="fas fa-info-circle"></i>
            <strong>Demo:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 4000);
    }
    
    // Debug function to show tracking info
    showTrackingDebugInfo() {
        if (!window.gogolAnalytics) {
            console.log('Demo: Tracking script not loaded yet');
            return;
        }
        
        const debugInfo = {
            visitorUuid: window.gogolAnalytics.getVisitorUuid(),
            sessionId: window.gogolAnalytics.getSessionId(),
            pageUrl: window.location.href,
            pageTitle: document.title,
            userAgent: navigator.userAgent,
            screenResolution: `${screen.width}x${screen.height}`,
            viewportSize: `${window.innerWidth}x${window.innerHeight}`,
            timestamp: new Date().toISOString()
        };
        
        console.table(debugInfo);
        return debugInfo;
    }
}

// Initialize demo functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.demoWebsite = new DemoWebsite();
    
    // Add debug function to global scope
    window.showTrackingDebugInfo = () => window.demoWebsite.showTrackingDebugInfo();
    
    // Show welcome notification after a short delay
    setTimeout(() => {
        if (window.demoWebsite) {
            window.demoWebsite.showDemoNotification(
                'This website is being tracked! Check the analytics dashboard to see your activity.',
                'success'
            );
        }
    }, 2000);
});

// Add some CSS for demo notifications
const style = document.createElement('style');
style.textContent = `
    .demo-notification {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .demo-notification .fas {
        margin-right: 0.5rem;
    }
`;
document.head.appendChild(style);