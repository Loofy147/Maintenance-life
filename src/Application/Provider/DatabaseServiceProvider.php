<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(\PDO::class, function($c) {
            $paths = $c->get('paths');
            $storagePath = $paths['storage'] . '/database.sqlite';
            $pdo = new \PDO('sqlite:' . $storagePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });
    }
}