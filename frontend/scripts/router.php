<?php
/**
 * Frontend Router for Multi-Tenant Booking System
 * 
 * This allows URLs like /acme-driving/ to work without creating folders.
 * All requests are routed to the same HTML file.
 */

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Parse the path
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = array_filter(explode('/', $path));

// Determine which file to serve
$fileToServe = 'driving_school_app.html';

// Check if requesting a specific file
if (!empty($pathParts)) {
    $lastPart = end($pathParts);
    
    // If last part has an extension, it's a file request
    if (strpos($lastPart, '.') !== false) {
        $fileToServe = $lastPart;
    }
}

// Define allowed files (security)
$allowedFiles = [
    'driving_school_app.html',
    'index.html',
    'portal.html',
    'test-detection.html'
];

// Check if file exists and is allowed
if (in_array($fileToServe, $allowedFiles) && file_exists($fileToServe)) {
    // Set content type
    $extension = pathinfo($fileToServe, PATHINFO_EXTENSION);
    $contentTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json'
    ];
    
    if (isset($contentTypes[$extension])) {
        header('Content-Type: ' . $contentTypes[$extension]);
    }
    
    // Serve the file
    readfile($fileToServe);
    exit;
}

// If file not found, return 404
http_response_code(404);
echo "<!DOCTYPE html>
<html>
<head>
    <title>404 - Not Found</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; }
        h1 { color: #dc2626; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>404 - Not Found</h1>
    <p>The requested path <code>{$requestUri}</code> was not found.</p>
    <p><a href='/'>Go to home page</a></p>
</body>
</html>";

