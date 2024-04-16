<?php

namespace App;

use Composer\InstalledVersions;
use Diana\Database\DatabasePackage;
use Diana\Rendering\Contracts\Renderer;
use Diana\Routing\Attributes\Command;
use Diana\Routing\Attributes\CommandErrorHandler;
use Diana\Routing\Attributes\Get;
use Diana\Routing\Attributes\HttpErrorHandler;

class AppController
{
    #[Get("/")]
    public function index(Renderer $renderer, AppPackage $appPackage)
    {
        return $renderer->make("./res/app.blade.php", $appPackage->config->get());
    }

    #[CommandErrorHandler()]
    public function commandError(int $errorCode)
    {
        return $errorCode;
    }

    #[HttpErrorHandler()]
    public function httpError(int $errorCode)
    {
        return $errorCode;
    }

    #[Get("/data")]
    public function data(AppPackage $appPackage)
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


    #[Command('db')]
    public function databaseTest(DatabasePackage $databasePackage)
    {
        var_dump($databasePackage->getConnection('default'));
        die;
    }
}