<?php

/**
* Diana - a php framework aiming for simplicity and clean syntax
*
* @package Diana
* @author  Antonio Ianzano
*/

use Diana\Runtime\Application;
use Diana\Runtime\ContainerProxy;

define('DIANA_BOOT', hrtime(true));

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
$container = new ContainerProxy();

(new Application(
    path: dirname(__DIR__),
    output: 'php://output',
    sapi: PHP_SAPI,
    loader: $autoLoader,
    container: $container
))->boot();
