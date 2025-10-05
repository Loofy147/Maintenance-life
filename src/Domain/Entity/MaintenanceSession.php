<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

use MaintenancePro\Domain\ValueObjects\TimePeriod;

class MaintenanceSession
{
    private ?int $id = null;
    private MaintenanceStatus $status;
    private TimePeriod $period;
    private string $reason;
    private array $metadata;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        TimePeriod $period,
        string $reason,
        array $metadata = []
    ) {
        $this->period = $period;
        $this->reason = $reason;
        $this->metadata = $metadata;
        $this->status = MaintenanceStatus::SCHEDULED;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function start(): void
    {
        if ($this->status !== MaintenanceStatus::SCHEDULED) {
            throw new \LogicException('Can only start a scheduled maintenance session');
        }

        $this->status = MaintenanceStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== MaintenanceStatus::ACTIVE) {
            throw new \LogicException('Can only complete an active maintenance session');
        }

        $this->status = MaintenanceStatus::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === MaintenanceStatus::COMPLETED) {
            throw new \LogicException('Cannot cancel a completed maintenance session');
        }

        $this->status = MaintenanceStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->status === MaintenanceStatus::ACTIVE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): MaintenanceStatus
    {
        return $this->status;
    }

    public function getPeriod(): TimePeriod
    {
        return $this->period;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}