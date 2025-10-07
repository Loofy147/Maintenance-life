<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use MaintenancePro\Infrastructure\Cache\FileCache;

/**
 * Registers the application's cache services.
 *
 * This provider sets up the cache system, binding the CacheInterface to a
 * concrete implementation (AdaptiveCache with a FileCache backend).
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the cache services in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(CacheInterface::class, function ($c) {
            $paths = $c->get('paths');
            $fileCache = new FileCache($paths['cache']);
            return new AdaptiveCache($fileCache);
        });
    }
}