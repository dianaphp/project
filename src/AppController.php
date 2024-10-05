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
use Diana\Runtime\Application;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AppController
{
    public function __construct(
        protected Application $app
    ) {
    }

    #[Get("/test")]
    public function test(): mixed
    {
        User::firstOrCreate(['name' => 'test', 'email' => 'test@test.com']);
        return User::all();
    }

    #[Get("/")]
    public function blade(Renderer $renderer, AppPackage $appPackage): string
    {
        // TODO: allow to use multiple renderers, and use the the renderingpackage to hold it
        // basically the same principle as the databasepackage, where you get the corresponding
        // connection with $databasePackage->getConnection('name')

        // TODO: for easier use, we can leave it just like it is right now
        // so the default renderer will be bound to the container and can automatically be accessed
        return $renderer->render($this->app->path("./res/app.blade.php"), $appPackage->getConfig()->get());
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function twig(TwigRenderer $twig, AppPackage $appPackage): string
    {
        return $twig->render("./res/app.twig", $appPackage->getConfig());
    }

    #[CommandErrorHandler()]
    public function commandError(int $errorCode): int
    {
        return $errorCode;
    }

    #[HttpErrorHandler()]
    public function httpError(int $errorCode): int
    {
        return $errorCode;
    }

    #[Get("/data")]
    public function data(AppPackage $appPackage): array
    {
        return [
            'name' => $appPackage->getConfig()->get('name'),
            'dianaVersion' => InstalledVersions::getVersion('dianaphp/framework'),
            'phpVersion' => phpversion()
        ];
    }

    #[Command('version', ['package'])]
    public function version($package = 'dianaphp/framework'): ?string
    {
        return InstalledVersions::getVersion($package);
    }


    #[Command('db')]
    public function databaseTest(DatabasePackage $databasePackage): void
    {
        $connection = $databasePackage->getConnection('default');
        // var_dump($connection);
        $id = "1--test";
        $result = $connection->read("SELECT * FROM test WHERE `id`=$id");
        var_dump($result);
    }
}