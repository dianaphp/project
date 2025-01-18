<?php

namespace App;

use Diana\Config\Config;
use Diana\Contracts\Core\Container;
use Diana\Contracts\Event\Dispatcher;
use Diana\Events\ShutdownEvent;
use Diana\Framework\Core\Application;
use Diana\Rendering\Compiler;
use Diana\Rendering\Components\Component;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Engines\FileEngine;
use Diana\Rendering\Engines\PhpEngine;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RenderingPackage
{
    public function __construct(
        protected Container $container,
        protected Application $app,
        #[Config('rendering')] protected Config $config,
        protected Dispatcher $dispatcher
    ) {
        $config->addDefault($this->getDefaultConfig());

        $container->singleton(BladeRenderer::class, fn(): BladeRenderer => $this->bladeRenderer());
        $container->singleton(TwigRenderer::class, fn(): TwigRenderer => $this->twigRenderer());
    }

    public function bladeRenderer(): BladeRenderer
    {
        Component::setCompilationPath($this->app->path($this->config->get('renderCompilationPath')));

        $compiler = new Compiler($this->app->path($this->config->get('renderCachePath')), false);
        $compiler->component('dynamic-component', DynamicComponent::class);
        $compiler->directive("vite", function ($entry) {
            $entry = trim($entry, "\"'");

            if ($this->config->get('viteEnv') == 'dev') {
                return
                    '<script type="module">
                        import RefreshRuntime from "' . $this->config->get('viteHost') . '/@react-refresh"
                        RefreshRuntime.injectIntoGlobalHook(window)
                        window.$RefreshReg$ = () => {}
                        window.$RefreshSig$ = () => (type) => type
                        window.__vite_plugin_react_preamble_installed__ = true
                    </script>
                    <script type="module" src="' . $this->config->get('viteHost') . '/@vite/client"></script>
                    <script type="module" src="' . $this->config->get('viteHost') . '/' . $entry . '"></script>';
            } else {
                $content = file_get_contents($this->app->path('dist/.vite/manifest.json'));
                $manifest = json_decode($content, true);

                if (!isset($manifest[$entry])) {
                    return "";
                }

                $script = "<script type=\"module\" src=\"" . $manifest[$entry]['file'] . "\"></script>";

                foreach ($manifest[$entry]['imports'] ?? [] as $imports) {
                    $script .= "\n<link rel=\"modulepreload\" href=\"/" . $manifest[$imports]['file'] . "\">";
                }
                foreach ($manifest[$entry]['css'] ?? [] as $file) {
                    $script .= "\n<link rel=\"stylesheet\" href=\"/$file\">";
                }

                return $script;
            }
        });

        $bladeEngine = new CompilerEngine($compiler);

        $this->dispatcher->addEventListener(ShutdownEvent::class, function () use ($bladeEngine) {
            Component::flushCache();
            $bladeEngine->forgetCompiledOrNotExpired();
        });

        $renderer = new BladeRenderer($compiler);

        $renderer->registerEngine('blade.php', $bladeEngine);
        $renderer->registerEngine('php', PhpEngine::class);
        $renderer->registerEngine(['html', 'css'], FileEngine::class);

        // TODO: remove this from container, this has to be bound to the bladerenderer instance only
        // if needed from the outside, it should be accessed via the bladerenderer->getCompiler()
        $this->container->instance(Compiler::class, $compiler);

        return $renderer;
    }

    public function twigRenderer(): TwigRenderer
    {
        $loader = new FilesystemLoader($this->app->path('res'));
        $environment = new Environment($loader, [
            'cache' => $this->app->path($this->config->get('renderCachePath')),
            'debug' => true
        ]);

        return new TwigRenderer($loader, $environment);
    }

    public function getDefaultConfig(): array
    {
        return [
            'renderer' => BladeRenderer::class,
            'renderCachePath' => 'tmp/rendering/cached',
            'renderCompilationPath' => 'tmp/rendering/compiled',
            'viteEnv' => 'env',
            'viteHost' => 'http://localhost:3000',
        ];
    }
}
