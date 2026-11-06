<?php

namespace App;

use Diana\Config\Config;
use Diana\Framework\Core\Application;
use Diana\Framework\Core\Environment;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Drivers\BladeRenderer;
use JsonException;
use RuntimeException;

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
            'viteBase' => '/',
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
            }

            return $this->productionAssets($entry);
        });

        $app->registerPackage(AppController::class);
    }

    private function productionAssets(string $entry): string
    {
        $path = $this->app->path('dist/.vite/manifest.json');
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Cannot read Vite manifest: $path. Run npm run build before deployment.");
        }
        try {
            $manifest = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid Vite manifest: $path", 0, $exception);
        }
        if (!is_array($manifest) || $manifest === [] || array_is_list($manifest)) {
            throw new RuntimeException("Invalid Vite manifest: $path (expected an entry map).");
        }

        $base = $this->config->get('viteBase');
        if (!is_string($base) || !preg_match('~^(?:/(?!/)|https?://[^/]+(?:/|$))~', $base)) {
            throw new RuntimeException('viteBase must be a root-relative path or an HTTP(S) URL.');
        }
        $url = static fn (string $file): string => htmlspecialchars(
            rtrim($base, '/') . '/' . ltrim($file, '/'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
        );
        $visited = [];
        $styles = [];
        $preloads = [];
        $visit = function (string $key) use (&$visit, &$visited, &$styles, &$preloads, $manifest, $entry): void {
            if (isset($visited[$key])) {
                return;
            }
            $chunk = $manifest[$key] ?? null;
            if (!is_array($chunk) || !is_string($chunk['file'] ?? null) || $chunk['file'] === '') {
                throw new RuntimeException("Missing or invalid Vite manifest entry: $key");
            }
            foreach (['imports', 'css'] as $field) {
                if (array_key_exists($field, $chunk) && (!is_array($chunk[$field]) || !array_is_list($chunk[$field]))) {
                    throw new RuntimeException("Invalid Vite manifest $field for entry: $key");
                }
                foreach ($chunk[$field] ?? [] as $value) {
                    if (!is_string($value) || $value === '') {
                        throw new RuntimeException("Invalid Vite manifest $field for entry: $key");
                    }
                }
            }
            $visited[$key] = true;
            // Imported styles precede entry styles, preserving the entry's cascade priority.
            foreach ($chunk['imports'] ?? [] as $import) {
                $visit($import);
            }
            foreach ($chunk['css'] ?? [] as $file) {
                $styles[$file] = true;
            }
            if ($key !== $entry) {
                $preloads[$chunk['file']] = true;
            }
        };
        $visit($entry);

        $tags = ['<script type="module" src="' . $url($manifest[$entry]['file']) . '"></script>'];
        foreach (array_keys($preloads) as $file) {
            $tags[] = '<link rel="modulepreload" href="' . $url($file) . '">';
        }
        foreach (array_keys($styles) as $file) {
            $tags[] = '<link rel="stylesheet" href="' . $url($file) . '">';
        }
        return implode("\n", $tags);
    }

    public function config(): Config
    {
        return $this->config;
    }
}
