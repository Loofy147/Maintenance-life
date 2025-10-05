<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use MaintenancePro\Infrastructure\Cache\FileCache;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(CacheInterface::class, function($c) {
            $paths = $c->get('paths');
            $fileCache = new FileCache($paths['cache']);
            return new AdaptiveCache($fileCache);
        });
    }
}