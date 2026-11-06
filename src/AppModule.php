<?php

namespace App;

use Diana\Config\Config;
use Diana\Framework\Core\Application;
use Diana\Framework\Core\Environment;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Drivers\BladeRenderer;

class AppModule
{
    public function __construct(
        #[Config] protected Config $config,
        protected Application $app,
        BladeRenderer $renderer,
        protected Environment $environment
    ) {
        $this->config->addDefault([
            'viteHost' => 'http://localhost:3000',
        ]);

        $compiler = $renderer->getCompiler();
        $compiler->component('dynamic-component', DynamicComponent::class);
        $compiler->directive("vite", function ($entry) {
            $entry = trim($entry, "\"'");

            if ($this->environment->is(Environment::DEV)) {
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

        $app->registerPackage(AppController::class);
    }

    public function config(): Config
    {
        return $this->config;
    }
}
