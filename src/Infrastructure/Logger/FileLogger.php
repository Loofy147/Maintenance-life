<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Logger;

/**
 * A simple PSR-3 compliant logger that writes messages to a file.
 *
 * @deprecated This class is a basic logger; MonologLogger is recommended for production use.
 */
class FileLogger implements LoggerInterface
{
    private string $logFile;
    private string $dateFormat = 'Y-m-d H:i:s';

    /**
     * FileLogger constructor.
     *
     * @param string $logFile The path to the log file.
     */
    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Logs a message with an arbitrary level.
     *
     * @param mixed                $level   The log level.
     * @param string               $message The log message.
     * @param array<string, mixed> $context The log context.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $timestamp = date($this->dateFormat);
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}