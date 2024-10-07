<?php

namespace App;

use Diana\Runtime\Application;
use Diana\Runtime\Attributes\Config;
use Diana\Runtime\Package;
use Diana\Drivers\ConfigInterface;
use Diana\Drivers\Routing\RouterInterface;

class AppPackage extends Package
{
    /** This package is being initialized */
    /** Register Drivers, Packages, Controllers here */
    public function __construct(
        Application $app,
        RouterInterface $router,
        #[Config('app')] protected ConfigInterface $config
    ) {
        $app->registerPackage(
            RenderingPackage::class
        );

        $router->registerController(AppController::class);
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}
