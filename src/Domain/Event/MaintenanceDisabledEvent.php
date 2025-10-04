<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

use MaintenancePro\Domain\Entity\MaintenanceSession;

class MaintenanceDisabledEvent extends BaseEvent
{
    public function __construct(MaintenanceSession $session)
    {
        parent::__construct('maintenance.disabled', [
            'session_id' => $session->getId(),
            'completed_at' => (new \DateTimeImmutable())->format('c')
        ]);
    }
}