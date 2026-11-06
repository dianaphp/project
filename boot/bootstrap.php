<?php

/**
 * Diana - a php framework aiming for simplicity and clean syntax
 *
 * @package Diana
 * @author  Antonio Ianzano
 */

use Diana\Config\FileConfig;
use Diana\Framework\Core\Application;
use Diana\IO\Request;

if (!defined('DIANA_BOOT')) {
    define('DIANA_BOOT', hrtime(true));
}

// Protect even Composer loading failures, before Whoops is available.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(static function (\Throwable $exception): void {
    error_log((string) $exception);
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo "Internal Server Error\n";
    exit(1);
});

/**
 * Class loader
 * -----------------------------------------------
 * Thanks to composer, we have the opportunity to include
 * a simple yet convenient class loader for our project.
 * It will handle all the namespacing for us.
 */
$autoLoader = require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Crafting the project
 * -----------------------------------------------
 * It is time to craft the actual project.
 * This process will bootstrap the newly created project and set up
 * all by itself so we can start developing straight away.
 */
$appPath = dirname(__DIR__);

FileConfig::setDirectory($appPath . DIRECTORY_SEPARATOR . 'cfg');

(new Application(
    appPath: $appPath,
    loader: $autoLoader,
    config: static fn(): FileConfig => new FileConfig('framework')
))->handleRequest(Request::capture());
