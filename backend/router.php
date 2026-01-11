<?php
// Router for PHP built-in server
// This file routes all requests to api.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the file exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route to api.php
require_once __DIR__ . '/api_v2.php';

