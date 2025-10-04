<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Service\Mock;

class MockExternalService
{
    private string $failureFlagPath;

    public function __construct(string $storagePath)
    {
        $this->failureFlagPath = $storagePath . '/mock_service.fail';
    }

    /**
     * @throws \Exception
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