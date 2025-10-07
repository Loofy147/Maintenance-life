<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Logger;

use MaintenancePro\Application\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * An implementation of the application's LoggerInterface that uses the Monolog library.
 */
class MonologLogger implements LoggerInterface
{
    private PsrLoggerInterface $logger;

    /**
     * MonologLogger constructor.
     *
     * @param string $logFile The path to the log file where messages will be written.
     */
    public function __construct(string $logFile)
    {
        $this->logger = new Logger('maintenance-pro');
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}