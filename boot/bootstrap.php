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
    config: new FileConfig('framework')
))->handleRequest(Request::capture());
