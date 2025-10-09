<?php
declare(strict_types=1);

namespace Tests\Integration;

use MaintenancePro\Application\Kernel;

trait IntegrationTestBehaviour
{
    protected Kernel $kernel;
    protected string $storagePath;
    protected string $configPath;
    protected string $rootPath;

    protected function setupKernel(): void
    {
        putenv('APP_ENV=testing');

        $this->rootPath = dirname(__DIR__, 2);
        $this->storagePath = $this->rootPath . '/var/storage';
        $this->configPath = $this->rootPath . '/config';

        // Ensure a clean state for storage
        if (is_dir($this->storagePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->storagePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
        } else {
            mkdir($this->storagePath, 0755, true);
        }

        // Create a temporary config file for tests
        copy($this->configPath . '/config.example.json', $this->configPath . '/config.json');

        $this->kernel = new Kernel($this->rootPath);
    }

    protected function tearDownKernel(): void
    {
        // Restore PHPUnit's error and exception handlers
        restore_error_handler();
        restore_exception_handler();

        // Clean up the temporary config file
        if (file_exists($this->configPath . '/config.json')) {
            unlink($this->configPath . '/config.json');
        }

        // Clean up storage files
        if (file_exists($this->storagePath . '/maintenance.flag')) {
            unlink($this->storagePath . '/maintenance.flag');
        }
        if (file_exists($this->storagePath . '/whitelist.json')) {
            unlink($this->storagePath . '/whitelist.json');
        }

        // Unset server variables to avoid side effects
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
        if (isset($_GET['range'])) {
            unset($_GET['range']);
        }
    }
}