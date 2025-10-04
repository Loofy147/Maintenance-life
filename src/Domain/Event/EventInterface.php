<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

interface EventInterface
{
    public function getName(): string;
    public function getTimestamp(): int;
    public function getData(): array;
    public function isPropagationStopped(): bool;
    public function stopPropagation(): void;
}