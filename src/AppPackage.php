<?php

namespace App;

use Diana\Runtime\Application;
use Diana\Runtime\Attributes\Config;
use Diana\Runtime\Package;
use Diana\Drivers\ConfigInterface;
use Diana\Drivers\Routing\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AppPackage extends Package
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
