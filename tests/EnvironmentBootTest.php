<?php

namespace Tests;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

class EnvironmentBootTest extends TestCase
{
    private string $caseDescription = '';

    public function testRealHttpAndCliBootPathsUseSafeDefaultsAndExplicitDevelopment(): void
    {
        $directory = sys_get_temp_dir() . '/diana-boot-' . bin2hex(random_bytes(8));
        foreach (['boot', 'dist', 'bin', 'vendor', 'cfg', 'tmp'] as $part) {
            mkdir($directory . '/' . $part, 0777, true);
        }
        foreach (['boot/bootstrap.php', 'boot/server.php', 'dist/index.php', 'bin/diana'] as $file) {
            copy(dirname(__DIR__) . '/' . $file, $directory . '/' . $file);
        }
        $autoload = dirname((new \ReflectionClass(ClassLoader::class))->getFileName(), 2) . '/autoload.php';
        file_put_contents($directory . '/vendor/autoload.php', "<?php class BootFailureEntrypoint { public function __invoke() { throw new \\RuntimeException('private-boot-secret'); } } return require " . var_export($autoload, true) . ';');
        $failure = "'entrypoint' => \\BootFailureEntrypoint::class,";
        $cases = [
            // APP_ENV, configuration source, expected detail marker (null means generic).
            [null, null, null],
            ['production', null, null],
            ['prod', null, null],
            ['dev', null, null],
            [null, '<?php return [', null],
            ['dev', '<?php return [', null],
            [null, '<?php return null;', null],
            ['dev', '<?php return null;', null],
            [null, "<?php trigger_error('private-boot-secret', E_USER_WARNING); return [];", null],
            [null, "<?php return ['singleton' => [\\Diana\\Contracts\\Core\\Container::class => 'private-boot-secret']];", null],
            ['dev', "<?php return ['singleton' => [\\Diana\\Contracts\\Core\\Container::class => 'private-boot-secret']];", null],
            [null, "<?php return [$failure];", null],
            [null, "<?php return ['env' => 'dev', $failure];", 'private-boot-secret'],
            ['dev', "<?php return ['env' => 'production', $failure];", 'private-boot-secret'],
            ['production', "<?php return ['env' => 'dev', $failure];", null],
            ['prod', "<?php return ['env' => 'dev', $failure];", null],
            [' ', "<?php return ['env' => 'dev', $failure];", null],
            ['DEV', "<?php return ['env' => 'dev', $failure];", null],
        ];

        try {
            foreach ($cases as [$environment, $source, $detail]) {
                $this->caseDescription = json_encode([$environment, $source]);
                $config = $directory . '/cfg/framework.conf.php';
                if (is_file($config)) {
                    unlink($config);
                }
                if ($source !== null) {
                    file_put_contents($config, $source);
                }
                $env = getenv();
                unset($env['APP_ENV']);
                if ($environment !== null) {
                    $env['APP_ENV'] = $environment;
                }
                foreach (['dist/index.php', 'boot/server.php'] as $entry) {
                    $this->assertHttpFailure($directory, $entry, $env, $detail);
                }
                $log = tmpfile();
                $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', $directory . '/bin/diana'],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => $log], $pipes, $directory, $env);
                self::assertIsResource($process);
                fclose($pipes[0]);
                $body = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                self::assertSame(1, proc_close($process), 'CLI must fail with exit code 1.');
                $this->assertBody($body, $detail);
                fclose($log);
                if ($source !== null && str_contains($source, "'env' => 'production'")) {
                    self::assertSame('production', (require $config)['env'], 'APP_ENV must not be persisted.');
                }
            }

            // Even a missing Composer loader must use the minimal safe fallback.
            unlink($directory . '/vendor/autoload.php');
            $env['APP_ENV'] = 'dev';
            foreach (['dist/index.php', 'boot/server.php'] as $entry) {
                $this->assertHttpFailure($directory, $entry, $env, null);
            }
        } finally {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($directory);
        }
    }

    private function assertBody(string $body, ?string $detail): void
    {
        if ($detail === null) {
            self::assertTrue($body === "Internal Server Error\n", $this->caseDescription . substr($body, 0, 250));
        } else {
            self::assertStringContainsString($detail, $body);
        }
    }

    private function assertHttpFailure(string $directory, string $entry, array $env, ?string $detail): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        self::assertNotFalse($socket, $error);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $log = tmpfile();
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-S', $address, '-t', $directory . '/dist', $directory . '/' . $entry],
            [0 => ['pipe', 'r'], 1 => $log, 2 => $log], $pipes, $directory, $env);
        self::assertIsResource($process);
        try {
            $ready = false;
            for ($attempt = 0; $attempt < 100; $attempt++) {
                $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
                if ($connection) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(20000);
            }
            self::assertTrue($ready, 'HTTP server did not start.');
            $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
            $stream = fopen('http://' . $address . '/failure', 'r', false, $context);
            self::assertIsResource($stream);
            try {
                $body = stream_get_contents($stream);
                $headers = stream_get_meta_data($stream)['wrapper_data'];
            } finally {
                fclose($stream);
            }
            self::assertStringContainsString('500', $headers[0]);
            $this->assertBody($body, $detail);
            if ($detail === null) {
                self::assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
                rewind($log);
                self::assertStringContainsString('Stack trace', stream_get_contents($log));
            }
        } finally {
            proc_terminate($process);
            fclose($pipes[0]);
            proc_close($process);
            fclose($log);
        }
    }
}
