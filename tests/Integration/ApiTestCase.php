<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    use IntegrationTestBehaviour;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownKernel();
    }

    protected function get(string $uri): array
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $_GET);
        } else {
            $_GET = [];
        }

        ob_start();
        $this->kernel->run();
        $output = ob_get_clean();

        return json_decode($output, true) ?? [];
    }

    protected function post(string $uri, array $data = []): array
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode($data));
        rewind($stream);

        $GLOBALS['__PHPUNIT_OVERRIDE_PHP_INPUT_STREAM'] = $stream;

        ob_start();
        $this->kernel->run();
        $output = ob_get_clean();

        fclose($stream);
        unset($GLOBALS['__PHPUNIT_OVERRIDE_PHP_INPUT_STREAM']);

        return json_decode($output, true) ?? [];
    }

    protected function getPublic(string $uri, string $ip): string
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = $ip;
        $_GET = [];

        ob_start();
        $this->kernel->run();
        return ob_get_clean() ?: '';
    }
}