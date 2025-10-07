<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

/**
 * Defines the contract for an analytics service.
 *
 * This interface outlines the standard methods for tracking events, identifying users,
 * and retrieving metrics.
 */
interface AnalyticsServiceInterface
{
    /**
     * Tracks an event with associated properties.
     *
     * @param string               $event      The name of the event to track.
     * @param array<string, mixed> $properties Optional properties associated with the event.
     */
    public function track(string $event, array $properties = []): void;

    /**
     * Associates a user with a set of traits.
     *
     * @param string               $userId The unique identifier for the user.
     * @param array<string, mixed> $traits An array of traits to associate with the user.
     */
    public function identify(string $userId, array $traits = []): void;

    /**
     * Retrieves metrics based on tracked events.
     *
     * @param string               $metric  The name of the metric to retrieve.
     * @param array<string, mixed> $filters Optional filters to apply to the metric calculation.
     * @return array<string, mixed> An array representing the calculated metric.
     */
    public function getMetrics(string $metric, array $filters = []): array;
}