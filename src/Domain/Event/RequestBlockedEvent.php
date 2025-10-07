<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

use MaintenancePro\Domain\ValueObject\IPAddress;

/**
 * Event fired when a request is blocked by the application's security measures.
 */
class RequestBlockedEvent extends BaseEvent
{
    /**
     * RequestBlockedEvent constructor.
     *
     * @param IPAddress $ipAddress The IP address that was blocked.
     * @param string    $reason    The reason why the request was blocked.
     */
    public function __construct(IPAddress $ipAddress, string $reason)
    {
        parent::__construct('request.blocked', [
            'ip' => $ipAddress->toString(),
            'reason' => $reason
        ]);
    }
}