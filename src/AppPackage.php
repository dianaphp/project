<?php

namespace App;

use Diana\Database\DatabasePackage;
use Diana\IO\Response;
use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\RenderingPackage;
use Diana\IO\Contracts\Kernel;
use Diana\Routing\Contracts\Router;
use Diana\Runtime\Container;
use Diana\Runtime\Package;

class AppPackage extends Package
{
    /** This package is being initialized */
    /** Register Drivers, Packages, Controllers here */
    public function __construct(Container $container, Kernel $kernel)
    {
        $this->loadConfig();

        $kernel->registerPackage(
            RenderingPackage::class,
            DatabasePackage::class
        );

        $kernel->registerController(AppController::class);
    }

    public function getConfigFile(): string
    {
        return 'app';
    }

    public function getConfigDefault(): array
    {
        return [
            'name' => 'Diana Application',
            'version' => '1.0.0'
        ];
    }

    public function getConfigVisible(): array
    {
        return ['name', 'version'];
    }

    public function getConfigCreate(): bool
    {
        return true;
    }

    public function getConfigAppend(): bool
    {
        return true;
    }

    /** All packages have been registered */
    public function boot(Router $router, Kernel $kernel, Renderer $renderer, AppPackage $appPackage): void
    {
    }
}