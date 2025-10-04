<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

abstract class BaseEvent implements EventInterface
{
    private string $name;
    private int $timestamp;
    private array $data;
    private bool $propagationStopped = false;

    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->timestamp = time();
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}