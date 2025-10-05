<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Logger;

use MaintenancePro\Application\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

class MonologLogger implements LoggerInterface
{
    private PsrLoggerInterface $logger;

    public function __construct(string $logFile)
    {
        $this->logger = new Logger('maintenance-pro');
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
    }

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}