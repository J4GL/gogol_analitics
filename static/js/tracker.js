(function () {
    console.log("Gogol Analytics Tracker Loaded");

    async function collectAndSend() {
        try {
            // 1. Fetch Location & Proxy Info
            const ipResponse = await fetch('http://ip-api.com/json/?fields=status,message,country,countryCode,proxy,query');
            const ipData = await ipResponse.json();

            if (ipData.status !== 'success') {
                console.error("Failed to fetch IP data:", ipData.message);
                return;
            }

            // 2. Collect Metadata
            const userAgent = navigator.userAgent;
            const screenRes = `${window.screen.width}x${window.screen.height}`;
            const referrer = document.referrer;
            const currentUrl = window.location.href;

            // 3. Bot Detection
            let isBot = false;
            if (ipData.proxy) {
                isBot = true;
            }
            if (navigator.webdriver) {
                isBot = true;
            }

            // 4. Prepare Payload
            const payload = {
                country: ipData.country,
                country_code: ipData.countryCode,
                ip: ipData.query, // ip-api returns IP in 'query' field
                user_agent: userAgent,
                screen_resolution: screenRes,
                referrer: referrer,
                current_url: currentUrl,
                is_bot: isBot
            };

            // 5. Send to Backend
            await fetch('http://localhost:8091/api/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            console.log("Analytics data sent successfully", payload);

        } catch (error) {
            console.error("Error in Gogol Analytics Tracker:", error);
        }
    }

    // Execute when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', collectAndSend);
    } else {
        collectAndSend();
    }

})();
