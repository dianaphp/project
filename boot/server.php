<?php

$dist = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dist';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim(rawurldecode(is_string($path) ? $path : ''), '/');
if (is_file($dist . DIRECTORY_SEPARATOR . $uri)) {
    return false;
}

require_once __DIR__ . '/bootstrap.php';
