<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Defines the application's logging interface.
 *
 * This interface extends the PSR-3 LoggerInterface, ensuring that any logger
 * implementation used within the application adheres to a standard contract.
 */
interface LoggerInterface extends PsrLoggerInterface
{
}