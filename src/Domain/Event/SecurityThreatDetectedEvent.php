<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

class SecurityThreatDetectedEvent extends BaseEvent
{
    public function __construct(string $threatType, array $details)
    {
        parent::__construct('security.threat_detected', [
            'threat_type' => $threatType,
            'details' => $details
        ]);
    }
}