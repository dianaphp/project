<?php

namespace App;

use Composer\InstalledVersions;
use Diana\Contracts\EventListenerContract;
use Diana\Contracts\RendererContract;
use Diana\Database\DatabasePackage;
use Diana\Events\BootEvent;
use Diana\IO\Event\Attributes\EventListener;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Router\Attributes\Command;
use Diana\Router\Attributes\CommandErrorHandler;
use Diana\Router\Attributes\Get;
use Diana\Router\Attributes\HttpErrorHandler;
use Diana\Runtime\Framework;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AppController
{
    public function __construct(
        protected Framework $app
    ) {
    }

    #[Get("/test")]
    public function test(): mixed
    {
        User::firstOrCreate(['name' => 'test', 'email' => 'test@test.com']);
        return User::all();
    }

    #[EventListener(BootEvent::class)]
    public function onAppBoot(BootEvent $event, EventListenerContract $eventListener): void
    {
    }

    #[Get("/")]
    public function blade(RendererContract $renderer, AppModule $appModule): string
    {
        // TODO: allow to use multiple renderers, and use the the renderingpackage to hold it
        // basically the same principle as the databasepackage, where you get the corresponding
        // connection with $databasePackage->getConnection('name')

        // TODO: for easier use, we can leave it just like it is right now
        // so the default renderer will be bound to the container and can automatically be accessed
        return $renderer->render($this->app->path("res/app.blade.php"), $appModule->getConfig());
    }

    #[Get("/twig")]
    public function twig(TwigRenderer $twig, AppModule $appPackage): string
    {
        return $twig->render("res/app.twig", $appPackage->getConfig()->get());
    }

    #[CommandErrorHandler]
    public function commandError(int $statusCode): int
    {
        return $statusCode;
    }

    #[HttpErrorHandler]
    public function httpError(int $statusCode): int
    {
        return $statusCode;
    }

    #[Get("/data")]
    public function data(AppModule $appService): array
    {
        return [
            'name' => $appService->getConfig('name'),
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
        $connection = $databasePackage->getConnection();
        // Fvar_dump($connection);
        $id = "1--test";
        $result = $connection->read("SELECT * FROM test WHERE `id`=$id");
        var_dump($result);
    }
}