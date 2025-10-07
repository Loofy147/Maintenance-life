<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

/**
 * Event fired when a potential security threat is detected.
 */
class SecurityThreatDetectedEvent extends BaseEvent
{
    /**
     * SecurityThreatDetectedEvent constructor.
     *
     * @param string $threatType The type of threat detected (e.g., 'sql_injection', 'xss').
     * @param array  $details    Additional details about the detected threat.
     */
    public function __construct(string $threatType, array $details)
    {
        parent::__construct('security.threat_detected', [
            'threat_type' => $threatType,
            'details' => $details
        ]);
    }
}