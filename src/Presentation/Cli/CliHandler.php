<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Cli;

use MaintenancePro\Application\Kernel;
use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\Service\MetricsServiceInterface;

class CliHandler
{
    private Kernel $app;
    private array $argv;

    public function __construct(Kernel $app, array $argv)
    {
        $this->app = $app;
        $this->argv = $argv;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        /** @var MetricsServiceInterface $metrics */
        $metrics = $this->app->getContainer()->get(MetricsServiceInterface::class);

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

    private function commandDisable(): void
    {
        /** @var MaintenanceService $maintenance */
        $maintenance = $this->app->getContainer()->get(MaintenanceService::class);
        $maintenance->disable();

        echo "✓ Maintenance mode disabled\n";
    }

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

    private function commandMetricsReport(): void
    {
        /** @var MetricsServiceInterface $metrics */
        $metrics = $this->app->getContainer()->get(MetricsServiceInterface::class);
        $report = $metrics->generateReport();

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

EXAMPLES:
  php bin/console enable "Database upgrade" 7200
  php bin/console disable
  php bin/console status
  php bin/console whitelist:add 192.168.1.100

HELP;
    }
}