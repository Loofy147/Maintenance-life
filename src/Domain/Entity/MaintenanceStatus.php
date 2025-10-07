<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

/**
 * Defines the possible statuses for a maintenance session.
 */
enum MaintenanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}