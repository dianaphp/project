<?php

namespace Tests;

use App\AppModule;
use Composer\Autoload\ClassLoader;
use Diana\Config\Config;
use Diana\Config\FileConfig;
use Diana\Contracts\Core\Container;
use Diana\Contracts\Event\Dispatcher;
use Diana\Contracts\RendererContract;
use Diana\Framework\Core\Application;
use Diana\IO\Event\NullDispatcher;
use Diana\Rendering\Compiler;
use Diana\Rendering\RenderingPackage;
use Diana\Rendering\Components\Component;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Drivers\TwigRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AppModuleRenderingTest extends TestCase
{
    private string $directory;
    private array $configState;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/diana-app-rendering-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/cfg', 0777, true);
        mkdir($this->directory . '/res');
        mkdir($this->directory . '/tmp/rendering/cached', 0777, true);
        mkdir($this->directory . '/dist/.vite', 0777, true);
        foreach ([Config::class => 'configs', FileConfig::class => 'directory'] as $class => $name) {
            $property = new ReflectionProperty($class, $name);
            $this->configState[$class] = $property->isInitialized() ? $property->getValue() : null;
        }
        (new ReflectionProperty(Config::class, 'configs'))->setValue(null, []);
        FileConfig::setDirectory($this->directory . '/cfg');
        file_put_contents($this->directory . '/cfg/framework.conf.php', '<?php return ' . var_export([
            'entrypoint' => RenderingTestEntryPoint::class,
            'packages' => [RenderingPackage::class, AppModule::class],
            'singleton' => [Dispatcher::class => NullDispatcher::class],
        ], true) . ';');
        file_put_contents($this->directory . '/cfg/rendering.conf.php', '<?php return [];');
        file_put_contents($this->directory . '/res/page.blade.php', '@vite("main.jsx")' . "\n@env('dev')\nDevelopment environment\n@endenv");
        file_put_contents($this->directory . '/res/page.twig', 'Hello {{ name }}');
    }

    protected function tearDown(): void
    {
        foreach ($this->configState as $class => $value) {
            if ($value !== null) {
                (new ReflectionProperty($class, $class === Config::class ? 'configs' : 'directory'))->setValue(null, $value);
            } elseif ($class === FileConfig::class) {
                // Static typed properties cannot be unset; restore the project config directory.
                FileConfig::setDirectory(dirname(__DIR__) . '/cfg');
            }
        }
        Component::flushCache();
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    private function boot(string $env): Container
    {
        $frameworkPath = $this->directory . '/cfg/framework.conf.php';
        $framework = require $frameworkPath;
        $framework['env'] = $env;
        file_put_contents($frameworkPath, '<?php return ' . var_export($framework, true) . ';');
        file_put_contents($this->directory . '/cfg/app.conf.php', '<?php return ' . var_export([
            // A stale app setting must not override the framework environment.
            'env' => $env === 'dev' ? 'production' : 'dev',
            'viteHost' => 'http://localhost:3456',
        ], true) . ';');
        $app = new RenderingTestApplication($this->directory, new ClassLoader(), new FileConfig('framework'));
        return $app->container();
    }

    public function testAppModuleRegistersExtensionsOnTheRendererUsedByTheApplication(): void
    {
        $container = $this->boot('dev');
        $blade = $container->get(BladeRenderer::class);
        self::assertSame($blade, $container->get(RendererContract::class));
        self::assertFalse($container->has(Compiler::class));
        $renderingConfig = require $this->directory . '/cfg/rendering.conf.php';
        self::assertArrayNotHasKey('env', $renderingConfig);
        self::assertArrayNotHasKey('viteEnv', $renderingConfig);
        self::assertArrayNotHasKey('viteHost', $renderingConfig);
        self::assertSame(DynamicComponent::class, $blade->getCompiler()->getClassComponentAliases()['dynamic-component']);
        $html = $blade->render($this->directory . '/res/page.blade.php');
        self::assertStringContainsString('Development environment', $html);
        self::assertStringContainsString('http://localhost:3456/@vite/client', $html);
        self::assertStringContainsString('http://localhost:3456/main.jsx', $html);
        self::assertSame('Hello Diana', $container->get(TwigRenderer::class)->render('page.twig', ['name' => 'Diana']));
    }

    public function testAppModuleRetainsProductionViteManifestSupport(): void
    {
        file_put_contents($this->directory . '/dist/.vite/manifest.json', json_encode([
            'main.jsx' => ['file' => 'assets/main.js', 'imports' => ['shared'], 'css' => ['assets/main.css']],
            'shared' => ['file' => 'assets/shared.js'],
        ]));
        $container = $this->boot('production');
        $html = $container->get(BladeRenderer::class)->render($this->directory . '/res/page.blade.php');
        self::assertStringContainsString('src="assets/main.js"', $html);
        self::assertStringContainsString('href="/assets/shared.js"', $html);
        self::assertStringContainsString('href="/assets/main.css"', $html);
        self::assertStringNotContainsString('@vite/client', $html);
        self::assertStringNotContainsString('Development environment', $html);
    }
}

class RenderingTestApplication extends Application
{
    protected array $modules = [];

    public function container(): Container
    {
        return $this->container;
    }
}

class RenderingTestEntryPoint
{
    public function __invoke(): string
    {
        return '';
    }
}
