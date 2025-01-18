<?php

namespace App;

use Diana\Config\Config;
use Diana\Framework\Core\Application;

class AppModule
{
    public function __construct(
        #[Config] protected Config $config,
        Application $app
    ) {
        $app->registerPackage(AppController::class);

        $app->registerPackage(RenderingPackage::class);
    }

    public function config(): Config
    {
        return $this->config;
    }
}
