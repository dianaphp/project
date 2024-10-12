<?php

namespace App;

use Diana\Drivers\ConfigInterface;
use Diana\Runtime\Attributes\Config;
use Diana\Runtime\Framework;

class AppModule
{
    public function __construct(
        #[Config('app')] protected ConfigInterface $config,
        Framework $app
    ) {
        $app->registerPackage(AppController::class);

        $app->registerPackage(RenderingPackage::class);
    }

    public function getConfig(string $key = null): mixed
    {
        return $this->config->get($key);
    }
}
