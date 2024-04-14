<?php

namespace App;

use Diana\Rendering\RenderingPackage;
use Diana\IO\Contracts\Kernel;
use Diana\IO\Kernel as DianaKernel;
use Diana\Routing\RoutingPackage;
use Diana\Runtime\Application;
use Diana\Runtime\Container;
use Diana\Runtime\Package;

class AppPackage extends Package
{
    /** This package is being initialized */
    /** Register Drivers, Packages, Controllers here */
    public function __construct(Container $container, Application $app)
    {
        $this->loadConfig();

        $container->singleton(Kernel::class, DianaKernel::class);

        $app->registerPackage(
            RoutingPackage::class,
            RenderingPackage::class
        );

        $app->registerController(AppController::class);
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
    public function boot(): void
    {
    }
}