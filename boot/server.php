<?php

if(PHP_SAPI !== 'cli-server') {
    throw new RuntimeException('This script should only be called from the built-in PHP server.');
}

$dist = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dist';

$uri = trim($_SERVER['REQUEST_URI'], '/');

if (is_file($dist . DIRECTORY_SEPARATOR . $uri)) {
    return false;
}

require_once __DIR__ . '/bootstrap.php';