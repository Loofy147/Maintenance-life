<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

use MaintenancePro\Domain\ValueObjects\TimePeriod;

/**
 * Represents a maintenance session, defining its schedule, status, and associated details.
 */
class MaintenanceSession
{
    private ?int $id = null;
    private MaintenanceStatus $status;
    private TimePeriod $period;
    private string $reason;
    private array $metadata;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * MaintenanceSession constructor.
     *
     * @param TimePeriod $period   The scheduled time period for the maintenance.
     * @param string     $reason   The reason for the maintenance.
     * @param array      $metadata Additional data associated with the session.
     */
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

    /**
     * Starts the maintenance session.
     *
     * @throws \LogicException If the session is not in a scheduled state.
     */
    public function start(): void
    {
        if ($this->status !== MaintenanceStatus::SCHEDULED) {
            throw new \LogicException('Can only start a scheduled maintenance session');
        }

        $this->status = MaintenanceStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Completes the maintenance session.
     *
     * @throws \LogicException If the session is not in an active state.
     */
    public function complete(): void
    {
        if ($this->status !== MaintenanceStatus::ACTIVE) {
            throw new \LogicException('Can only complete an active maintenance session');
        }

        $this->status = MaintenanceStatus::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Cancels the maintenance session.
     *
     * @throws \LogicException If the session is not in a scheduled state.
     */
    public function cancel(): void
    {
        if ($this->status !== MaintenanceStatus::SCHEDULED) {
            throw new \LogicException('Cannot cancel a session that is not scheduled.');
        }

        $this->status = MaintenanceStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Checks if the maintenance session is currently active.
     *
     * @return bool True if the session is active, false otherwise.
     */
    public function isActive(): bool
    {
        return $this->status === MaintenanceStatus::ACTIVE;
    }

    /**
     * Gets the unique identifier for the maintenance session.
     *
     * @return int|null The session ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the current status of the maintenance session.
     *
     * @return MaintenanceStatus The session status.
     */
    public function getStatus(): MaintenanceStatus
    {
        return $this->status;
    }

    /**
     * Gets the scheduled time period for the maintenance.
     *
     * @return TimePeriod The maintenance period.
     */
    public function getPeriod(): TimePeriod
    {
        return $this->period;
    }

    /**
     * Gets the reason for the maintenance.
     *
     * @return string The maintenance reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Gets the metadata associated with the session.
     *
     * @return array The session metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}