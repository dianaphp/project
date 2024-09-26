<?php

namespace App;

use Diana\IO\Kernel;
use Diana\Rendering\Compiler;
use Diana\Rendering\Components\Component;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Engines\FileEngine;
use Diana\Rendering\Engines\PhpEngine;
use Diana\Runtime\Container;
use Diana\Runtime\Package;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Support\Helpers\Filesystem;
use Diana\Rendering\Exceptions\RendererException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RenderingPackage extends Package
{
    /**
     * @throws RendererException
     */
    public function __construct(Container $container, Kernel $kernel)
    {
        $this->loadConfig();

        $container->singleton(BladeRenderer::class, function () use ($container, $kernel) {
            Component::setCompilationPath(Filesystem::absPath($this->config['renderCompilationPath']));

            $compiler = new Compiler(Filesystem::absPath($this->config['renderCachePath']), false); // TODO: remove last argument to enable caching once everything works
            $compiler->component('dynamic-component', DynamicComponent::class);
            $compiler->directive("vite", function ($entry) {
                $entry = trim($entry, "\"'");

                if ($this->config['viteEnv'] == 'dev') {
                    return
                        '<script type="module">
                        import RefreshRuntime from "' . $this->config['viteHost'] . '/@react-refresh"
                        RefreshRuntime.injectIntoGlobalHook(window)
                        window.$RefreshReg$ = () => {}
                        window.$RefreshSig$ = () => (type) => type
                        window.__vite_plugin_react_preamble_installed__ = true
                    </script>
                    <script type="module" src="' . $this->config['viteHost'] . '/@vite/client"></script>
                    <script type="module" src="' . $this->config['viteHost'] . '/' . $entry . '"></script>';
                } else {
                    $content = file_get_contents(Filesystem::absPath('./dist/.vite/manifest.json'));
                    $manifest = json_decode($content, true);

                    $script = isset ($manifest[$entry]) ? "<script type=\"module\" src=\"" . $manifest[$entry]['file'] . "\"></script>" : "";

                    foreach ($manifest[$entry]['imports'] ?? [] as $imports) $script .= "\n<link rel=\"modulepreload\" href=\"/" . $manifest[$imports]['file'] . "\">";
                    foreach ($manifest[$entry]['css'] ?? [] as $file) $script .= "\n<link rel=\"stylesheet\" href=\"/$file\">";

                    return $script;
                }
            });

            $bladeEngine = new CompilerEngine($compiler);

            $renderer = new BladeRenderer($compiler);

            $renderer->registerEngine('blade.php', $bladeEngine);
            $renderer->registerEngine('php', PhpEngine::class);
            $renderer->registerEngine(['html', 'css'], FileEngine::class);

            $kernel->terminating(static function () use ($bladeEngine) {
                Component::flushCache();
                $bladeEngine->forgetCompiledOrNotExpired();
            });

            // TODO: remove this from container, this has to be bound to the bladerenderer instance only
            // if needed from the outside, it should be accessed via the bladerenderer->getCompiler()
            $container->instance(Compiler::class, $compiler);

            return $renderer;
        });

        $container->singleton(TwigRenderer::class, function () {
            $loader = new FilesystemLoader(Filesystem::absPath());
            $environment = new Environment($loader, [
                'cache' => Filesystem::absPath($this->config['renderCachePath']),
                'debug' => true
            ]);

            return new TwigRenderer($loader, $environment);
        });

        if (!is_a($this->config['renderer'], Renderer::class, true))
            throw new RendererException("Invalid renderer, expected instance of interface [" . Renderer::class . "], got [{$this->config['renderer']}].");

        $container->alias($this->config['renderer'], Renderer::class);
    }

    public function getConfigDefault(): array
    {
        return [
            'renderer' => BladeRenderer::class,
            'renderCachePath' => './tmp/rendering/cached',
            'renderCompilationPath' => './tmp/rendering/compiled',

            'viteEnv' => 'prod',
            'viteHost' => 'http://localhost:3000'
        ];
    }

    public function getConfigFile(): ?string
    {
        return 'rendering';
    }
}