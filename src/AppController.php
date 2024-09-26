<?php

namespace App;

use Composer\InstalledVersions;
use Diana\Database\DatabasePackage;
use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Routing\Attributes\Command;
use Diana\Routing\Attributes\CommandErrorHandler;
use Diana\Routing\Attributes\Get;
use Diana\Routing\Attributes\HttpErrorHandler;

class AppController
{
    #[Get("/")]
    public function index(TwigRenderer $twig, Renderer $renderer, AppPackage $appPackage)
    {
        return $twig->render("./res/app.twig", $appPackage->config->get());

        // TODO: allow to use multiple renderers, and use the the renderingpackage to hold it
        // basically the same principle as the databasepackage, where you get the corresponding
        // connection with $databasePackage->getConnection('name')

        // TODO: for easier use, we can leave it just like it is right now
        // so the default renderer will be bound to the container and can automatically be accessed
        return $renderer->render("./res/app.blade.php", $appPackage->config->get());
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
        $connection = $databasePackage->getConnection('default');
        // var_dump($connection);
        $id = "1--test";
        $result = $connection->read("SELECT * FROM test WHERE `id`=$id");
        var_dump($result);
        die;
    }
}