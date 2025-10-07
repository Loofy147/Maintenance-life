<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Cli;

use MaintenancePro\Application\Kernel;
use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Infrastructure\Service\Mock\MockExternalService;

/**
 * Handles all command-line interface (CLI) interactions for the application.
 *
 * This class parses command-line arguments and dispatches them to the appropriate
 * command methods.
 */
class CliHandler
{
    private Kernel $app;
    private array $argv;

    /**
     * CliHandler constructor.
     *
     * @param Kernel $app  The application kernel.
     * @param array  $argv The command-line arguments.
     */
    public function __construct(Kernel $app, array $argv)
    {
        $this->app = $app;
        $this->argv = $argv;
    }

    /**
     * Handles the incoming CLI request by parsing the command and executing it.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        /** @var MetricsInterface $metrics */
        $metrics = $this->app->getContainer()->get(MetricsInterface::class);

        $command = $this->argv[1] ?? 'help';
        $metrics->increment('cli.command.count', 1, ['command' => $command]);

        if ($command === 'help') {
            $this->showHelp();
            return;
        }

        $methodName = 'command' . str_replace([':', '-'], '', ucwords($command, ':-'));

        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            echo "Unknown command: {$command}\n";
            $this->showHelp();
        }

        $metrics->timing('cli.command.time', (microtime(true) - $startTime) * 1000, ['command' => $command]);
    }

    /**
     * Command to enable maintenance mode.
     */
    private function commandEnable(): void
    {
        $reason = $this->argv[2] ?? 'Manual maintenance';
        $duration = isset($this->argv[3]) ? (int)$this->argv[3] : 3600;

        /** @var MaintenanceService $maintenance */
        $maintenance = $this->app->getContainer()->get(MaintenanceService::class);
        $endTime = (new \DateTimeImmutable())->modify("+{$duration} seconds");

        $maintenance->enable($reason, $endTime);

        echo "✓ Maintenance mode enabled\n";
        echo "  Reason: {$reason}\n";
        echo "  End time: " . $endTime->format('Y-m-d H:i:s') . "\n";
    }

    /**
     * Command to disable maintenance mode.
     */
    private function commandDisable(): void
    {
        /** @var MaintenanceService $maintenance */
        $maintenance = $this->app->getContainer()->get(MaintenanceService::class);
        $maintenance->disable();

        echo "✓ Maintenance mode disabled\n";
    }

    /**
     * Command to check the current status of maintenance mode.
     */
    private function commandStatus(): void
    {
        /** @var MaintenanceService $maintenance */
        $maintenance = $this->app->getContainer()->get(MaintenanceService::class);

        if ($maintenance->isEnabled()) {
            echo "● Maintenance mode: ENABLED\n";
            $session = $maintenance->getCurrentSession();
            if ($session) {
                echo "  Reason: " . $session->getReason() . "\n";
                echo "  Started: " . $session->getPeriod()->getStart()->format('Y-m-d H:i:s') . "\n";
                echo "  Ends: " . $session->getPeriod()->getEnd()->format('Y-m-d H:i:s') . "\n";
            }
        } else {
            echo "● Maintenance mode: DISABLED\n";
        }
    }

    /**
     * Command to add an IP address to the whitelist.
     */
    private function commandWhitelistAdd(): void
    {
        $ip = $this->argv[2] ?? null;

        if (!$ip) {
            echo "Error: IP address required\n";
            echo "Usage: php bin/console whitelist:add <ip>\n";
            return;
        }

        /** @var AccessControlService $accessControl */
        $accessControl = $this->app->getContainer()->get(AccessControlService::class);
        $accessControl->addToWhitelist($ip);

        echo "✓ IP added to whitelist: {$ip}\n";
    }

    /**
     * Command to remove an IP address from the whitelist.
     */
    private function commandWhitelistRemove(): void
    {
        $ip = $this->argv[2] ?? null;

        if (!$ip) {
            echo "Error: IP address required\n";
            echo "Usage: php bin/console whitelist:remove <ip>\n";
            return;
        }

        /** @var AccessControlService $accessControl */
        $accessControl = $this->app->getContainer()->get(AccessControlService::class);
        $accessControl->removeFromWhitelist($ip);

        echo "✓ IP removed from whitelist: {$ip}\n";
    }

    /**
     * Command to display a report of performance metrics.
     */
    private function commandMetricsReport(): void
    {
        /** @var MetricsInterface $metrics */
        $metrics = $this->app->getContainer()->get(MetricsInterface::class);
        $report = $metrics->getReport();

        echo "📊 Performance Metrics Report (last 24 hours)\n";
        echo "================================================\n";

        if (empty($report)) {
            echo "No metrics recorded yet.\n";
            return;
        }

        foreach ($report as $key => $data) {
            echo "\nMetric: {$key}\n";
            echo "  Type: {$data['type']}\n";
            if ($data['type'] === 'counter') {
                echo "  Total: {$data['total']}\n";
            } else {
                echo "  Count: {$data['count']}\n";
                echo "  Avg: " . number_format($data['average'], 2) . "ms\n";
                echo "  Min: " . number_format($data['min'], 2) . "ms\n";
                echo "  Max: " . number_format($data['max'], 2) . "ms\n";
            }
        }
    }

    /**
     * Command to run system health checks.
     */
    private function commandHealthCheck(): void
    {
        /** @var HealthCheckAggregator $healthCheck */
        $healthCheck = $this->app->getContainer()->get(HealthCheckAggregator::class);
        $report = $healthCheck->runAll();

        echo "🩺 Health Check Report\n";
        echo "=======================\n";
        echo "Overall Status: " . strtoupper($report['status']) . "\n\n";

        foreach ($report['checks'] as $name => $check) {
            $status = $check['healthy'] ? '✅ HEALTHY' : '❌ UNHEALTHY';
            echo "Service: " . str_pad($name, 15) . " | Status: {$status}\n";
            echo "  Message: {$check['message']}\n";
            if (!empty($check['details'])) {
                echo "  Details: " . json_encode($check['details']) . "\n";
            }
        }
    }

    /**
     * Command to test the circuit breaker by calling a mock service.
     */
    private function commandMockServiceCall(): void
    {
        /** @var CircuitBreakerInterface $circuitBreaker */
        $circuitBreaker = $this->app->getContainer()->get(CircuitBreakerInterface::class);
        /** @var MockExternalService $mockService */
        $mockService = $this->app->getContainer()->get(MockExternalService::class);

        echo "Attempting to call mock external service...\n";
        try {
            $result = $circuitBreaker->call([$mockService, 'fetchData']);
            echo "✅ SUCCESS: " . json_encode($result) . "\n";
        } catch (\Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Command to set the mock service to a failing state.
     */
    private function commandMockServiceFail(): void
    {
        /** @var MockExternalService $mockService */
        $mockService = $this->app->getContainer()->get(MockExternalService::class);
        $mockService->setFailing(true);
        echo "Mock external service is now set to FAIL.\n";
    }

    /**
     * Command to set the mock service to a succeeding state.
     */
    private function commandMockServiceSucceed(): void
    {
        /** @var MockExternalService $mockService */
        $mockService = $this->app->getContainer()->get(MockExternalService::class);
        $mockService->setFailing(false);
        echo "Mock external service is now set to SUCCEED.\n";
    }

    /**
     * Displays the help message with all available commands.
     */
    private function showHelp(): void
    {
        echo <<<HELP

Enterprise Maintenance Mode CLI - v5.0.0

USAGE:
  php bin/console <command> [options]

COMMANDS:
  enable [reason] [duration]     Enable maintenance mode
                                 duration in seconds (default: 3600)

  disable                        Disable maintenance mode

  status                         Show current maintenance status

  whitelist:add <ip>            Add IP to whitelist

  whitelist:remove <ip>         Remove IP from whitelist

  metrics:report                 Generate a performance metrics report

  health:check                   Run a system health check

  mock:service-call              Call the mock external service (to test circuit breaker)
  mock:service-fail              Set the mock service to fail
  mock:service-succeed           Set the mock service to succeed

EXAMPLES:
  php bin/console enable "Database upgrade" 7200
  php bin/console disable
  php bin/console status
  php bin/console whitelist:add 192.168.1.100

HELP;
    }
}