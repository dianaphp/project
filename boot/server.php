<?php

$dist = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dist';
$uri = trim($_SERVER['REQUEST_URI'], '/');
if (is_file($dist . DIRECTORY_SEPARATOR . $uri)) {
    return false;
}

require_once __DIR__ . '/bootstrap.php';
