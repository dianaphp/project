<?php

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Diana\Rendering\Contracts\Renderer;
use Diana\Routing\Attributes\Command;
use Diana\Routing\Attributes\Get;
use Diana\Runtime\Application;
use Diana\Support\Debug;

class AppController
{
    #[Get("/")]
    public function index(Renderer $renderer, Application $app, AppPackage $appPackage)
    {
        return $renderer->make("./res/app.blade.php", $appPackage->config->get());
    }

    #[Get("/data")]
    public function data(Application $app, AppPackage $appPackage)
    {
        return [
            'name' => $appPackage->config['name'],
            'dianaVersion' => InstalledVersions::getVersion('dianaphp/framework'),
            'phpVersion' => phpversion()
        ];
    }

    #[Command('version', 'package')]
    public function version($package = 'dianaphp/framework')
    {
        return InstalledVersions::getVersion($package);
    }
}