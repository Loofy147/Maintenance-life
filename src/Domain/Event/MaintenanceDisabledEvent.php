<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

use MaintenancePro\Domain\Entity\MaintenanceSession;

/**
 * Event fired when a maintenance session is disabled.
 */
class MaintenanceDisabledEvent extends BaseEvent
{
    /**
     * MaintenanceDisabledEvent constructor.
     *
     * @param MaintenanceSession $session The maintenance session that was disabled.
     */
    public function __construct(MaintenanceSession $session)
    {
        parent::__construct('maintenance.disabled', [
            'session_id' => $session->getId(),
            'completed_at' => (new \DateTimeImmutable())->format('c')
        ]);
    }
}