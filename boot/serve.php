<?php

$host = getenv('DIANA_DEV_HOST') ?: 'localhost';
$command = [
    PHP_BINARY,
    '-S', $host . ':8000',
    '-t', dirname(__DIR__) . '/dist',
    __DIR__ . '/server.php',
];

passthru(implode(' ', array_map('escapeshellarg', $command)), $exitCode);
exit($exitCode);
