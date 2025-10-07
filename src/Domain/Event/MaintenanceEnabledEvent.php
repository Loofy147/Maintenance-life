<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

use MaintenancePro\Domain\Entity\MaintenanceSession;

/**
 * Event fired when a maintenance session is enabled.
 */
class MaintenanceEnabledEvent extends BaseEvent
{
    /**
     * MaintenanceEnabledEvent constructor.
     *
     * @param MaintenanceSession $session The maintenance session that was enabled.
     */
    public function __construct(MaintenanceSession $session)
    {
        parent::__construct('maintenance.enabled', [
            'session_id' => $session->getId(),
            'reason' => $session->getReason(),
            'period' => [
                'start' => $session->getPeriod()->getStart()->format('c'),
                'end' => $session->getPeriod()->getEnd()->format('c'),
            ]
        ]);
    }
}