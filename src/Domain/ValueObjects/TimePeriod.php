<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObjects;

/**
 * Represents an immutable period of time defined by a start and end datetime.
 *
 * This value object is used to define maintenance windows and other time-based
 * conditions in the application.
 */
final class TimePeriod
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    /**
     * @param \DateTimeImmutable $start The start of the time period.
     * @param \DateTimeImmutable $end The end of the time period.
     * @throws \InvalidArgumentException If the start time is after the end time.
     */
    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start time must be before end time');
        }

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Checks if a given datetime falls within this time period (inclusive).
     *
     * @param \DateTimeImmutable $dateTime The datetime to check.
     * @return bool True if the datetime is within the period, false otherwise.
     */
    public function contains(\DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime <= $this->end;
    }

    /**
     * Gets the duration of the time period as a DateInterval object.
     *
     * @return \DateInterval The duration of the period.
     */
    public function getDuration(): \DateInterval
    {
        return $this->start->diff($this->end);
    }

    /**
     * Gets the duration of the time period in seconds.
     *
     * @return int The total duration in seconds.
     */
    public function getDurationInSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * Gets the start time of the period.
     *
     * @return \DateTimeImmutable The start time.
     */
    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Gets the end time of the period.
     *
     * @return \DateTimeImmutable The end time.
     */
    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }
}