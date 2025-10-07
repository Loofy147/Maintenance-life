<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;

/**
 * Registers the database service.
 *
 * This provider sets up the PDO connection for the SQLite database and
 * registers it as a singleton in the service container.
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the PDO database connection in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(\PDO::class, function ($c) {
            $paths = $c->get('paths');
            $storagePath = $paths['storage'] . '/database.sqlite';
            $pdo = new \PDO('sqlite:' . $storagePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });
    }
}