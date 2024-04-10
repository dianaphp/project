<?php

/**
 * Diana - a php framework aiming for simplicity and clean syntax
 *
 * @package Diana
 * @author  Antonio Ianzano <ianzanoan@gmail.com>
 */

use Diana\IO\Request;
use Diana\Runtime\Application;

/**
 * Class loader
 * -----------------------------------------------
 * Thanks to composer, we have the opportunity to include
 * a simple yet convenient class loader for our project.
 * It will handle all of the namespacing for us.
 */
$autoLoader = require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Crafting the project
 * -----------------------------------------------
 * It is time to craft the actual project.
 * This process will bootstrap the newly created project and set up
 * all by itself so we can start developing straight away.
 */

Application::make(dirname(__DIR__), $autoLoader)
    ->handleRequest(Request::mock());