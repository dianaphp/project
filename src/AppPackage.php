<?php

namespace App;

use Diana\Rendering\RenderingPackage;
use Diana\IO\Contracts\Kernel as KernelContract;
use Diana\IO\Kernel;
use Diana\Routing\RoutingPackage;
use Diana\Runtime\Application;
use Diana\Runtime\Package;

class AppPackage extends Package
{
    /** This package is being initialized */
    /** Register Drivers, Packages, Controllers here */
    public function __construct(private Application $app)
    {
        $this->loadConfig();

        $this->app->singleton(KernelContract::class, Kernel::class);

        $this->app->registerPackage(
            RoutingPackage::class,
            RenderingPackage::class
        );

        $this->app->registerController(AppController::class);
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