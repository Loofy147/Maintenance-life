<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

use MaintenancePro\Domain\ValueObject\IPAddress;

class RequestBlockedEvent extends BaseEvent
{
    public function __construct(IPAddress $ipAddress, string $reason)
    {
        parent::__construct('request.blocked', [
            'ip' => $ipAddress->toString(),
            'reason' => $reason
        ]);
    }
}