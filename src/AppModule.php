<?php

namespace App;

use Diana\Contracts\ConfigContract;
use Diana\Runtime\Attributes\Config;
use Diana\Runtime\Framework;

class AppModule
{
    public function __construct(
        #[Config] protected ConfigContract $config,
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
