<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DevServerTest extends TestCase
{
    public static function requests(): iterable
    {
        yield 'asset' => ['/assets/diana.svg', false, ''];
        yield 'versioned asset' => ['/assets/diana.svg?v=1', false, ''];
        yield 'encoded filename' => ['/assets/with%20space.svg?v=1', false, ''];
        yield 'missing asset' => ['/assets/missing.svg?v=1', 1, 'application'];
        yield 'dynamic route' => ['/example?name=Max+Mustermann', 1, 'application'];
        yield 'root' => ['/?v=1', 1, 'application'];
    }

    #[DataProvider('requests')]
    public function testRouterDistinguishesStaticFilesFromApplicationRequests(string $uri, mixed $expected, string $output): void
    {
        $directory = sys_get_temp_dir() . '/diana-server-' . bin2hex(random_bytes(8));
        mkdir($directory . '/boot', 0777, true);
        mkdir($directory . '/dist/assets', 0777, true);
        copy(__DIR__ . '/../boot/server.php', $directory . '/boot/server.php');
        file_put_contents($directory . '/boot/bootstrap.php', '<?php echo "application";');
        file_put_contents($directory . '/dist/assets/diana.svg', '<svg/>');
        file_put_contents($directory . '/dist/assets/with space.svg', '<svg/>');
        $server = $_SERVER;
        $_SERVER['REQUEST_URI'] = $uri;
        ob_start();
        try {
            $result = require $directory . '/boot/server.php';
            self::assertSame($expected, $result);
            self::assertSame($output, ob_get_contents());
        } finally {
            ob_end_clean();
            $_SERVER = $server;
            foreach (['boot/server.php', 'boot/bootstrap.php', 'dist/assets/diana.svg', 'dist/assets/with space.svg'] as $file) {
                unlink($directory . '/' . $file);
            }
            foreach (['boot', 'dist/assets', 'dist', ''] as $path) {
                rmdir($directory . '/' . $path);
            }
        }
    }
}
