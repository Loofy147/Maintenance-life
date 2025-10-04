<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

enum MaintenanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}