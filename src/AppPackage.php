<?php

namespace App;

use Diana\Config\Attributes\Config;
use Diana\Runtime\Application;
use Diana\Runtime\Kernel;
use Diana\Runtime\Package;
use Illuminate\Container\Container;

class AppPackage extends Package
{
    /** This package is being initialized */
    /** Register Drivers, Packages, Controllers here */
    public function __construct(
        Application $app,
        Kernel $kernel,
        #[Config('app')] protected \Diana\Config\ConfigInterface $config
    ) {
        $app->registerPackage(
            RenderingPackage::class
        );

        $kernel->registerController(AppController::class);
    }

    public function getConfig(): \Diana\Config\ConfigInterface
    {
        return $this->config;
    }
}
