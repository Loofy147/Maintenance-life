<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObjects;

final class TimePeriod
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start time must be before end time');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function contains(\DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime <= $this->end;
    }

    public function getDuration(): \DateInterval
    {
        return $this->start->diff($this->end);
    }

    public function getDurationInSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }
}