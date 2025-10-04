<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObjects;

final class HealthStatusValue
{
    private const STATUS_HEALTHY = 'healthy';
    private const STATUS_UNHEALTHY = 'unhealthy';

    private string $status;
    private string $message;
    private array $details;

    private function __construct(string $status, string $message, array $details = [])
    {
        $this->status = $status;
        $this->message = $message;
        $this->details = $details;
    }

    public static function healthy(string $message, array $details = []): self
    {
        return new self(self::STATUS_HEALTHY, $message, $details);
    }

    public static function unhealthy(string $message, array $details = []): self
    {
        return new self(self::STATUS_UNHEALTHY, $message, $details);
    }

    public function isHealthy(): bool
    {
        return $this->status === self::STATUS_HEALTHY;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}