<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Service\Mock;

/**
 * A mock external service used for testing purposes, particularly for the circuit breaker.
 *
 * This service can be programmatically set to a failing state to simulate an outage.
 */
class MockExternalService
{
    private string $failureFlagPath;

    /**
     * MockExternalService constructor.
     *
     * @param string $storagePath The path to the storage directory where the failure flag file will be created.
     */
    public function __construct(string $storagePath)
    {
        $this->failureFlagPath = $storagePath . '/mock_service.fail';
    }

    /**
     * Simulates fetching data from an external service.
     *
     * @return array<string, mixed> The fetched data.
     * @throws \Exception If the service is in a failing state.
     */
    public function fetchData(): array
    {
        if (file_exists($this->failureFlagPath)) {
            throw new \Exception('Mock external service is currently unavailable.');
        }

        return [
            'data' => 'Successfully fetched data from external service.',
            'timestamp' => time(),
        ];
    }

    /**
     * Sets the service to a failing or passing state.
     *
     * @param bool $shouldFail If true, the service will be set to a failing state.
     */
    public function setFailing(bool $shouldFail): void
    {
        if ($shouldFail) {
            touch($this->failureFlagPath);
        } else {
            if (file_exists($this->failureFlagPath)) {
                unlink($this->failureFlagPath);
            }
        }
    }
}