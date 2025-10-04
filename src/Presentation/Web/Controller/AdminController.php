<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Web\Controller;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\Service\MetricsServiceInterface;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;

class AdminController
{
    private TemplateRendererInterface $renderer;
    private MaintenanceService $maintenanceService;
    private AccessControlService $accessControlService;
    private MetricsServiceInterface $metricsService;
    private ConfigurationManagerInterface $config;

    public function __construct(
        TemplateRendererInterface $renderer,
        MaintenanceService $maintenanceService,
        AccessControlService $accessControlService,
        MetricsServiceInterface $metricsService,
        ConfigurationManagerInterface $config
    ) {
        $this->renderer = $renderer;
        $this->maintenanceService = $maintenanceService;
        $this->accessControlService = $accessControlService;
        $this->metricsService = $metricsService;
        $this->config = $config;
    }

    public function index(): string
    {
        $data = [
            'title' => 'Admin Dashboard',
            'maintenance_status' => $this->maintenanceService->isEnabled(),
            'config' => $this->config->all(),
            'metrics' => $this->metricsService->generateReport(),
        ];
        return $this->renderer->render('admin/dashboard.phtml', $data);
    }

    public function enableMaintenance(): void
    {
        $reason = $_POST['reason'] ?? 'Enabled from admin dashboard';
        $this->maintenanceService->enable($reason);
        header('Location: /admin');
        exit;
    }

    public function disableMaintenance(): void
    {
        $this->maintenanceService->disable();
        header('Location: /admin');
        exit;
    }

    public function addWhitelistIp(): void
    {
        $ip = $_POST['ip'] ?? null;
        if ($ip) {
            $this->accessControlService->addToWhitelist($ip);
        }
        header('Location: /admin');
        exit;
    }

    public function removeWhitelistIp(): void
    {
        $ip = $_POST['ip'] ?? null;
        if ($ip) {
            $this->accessControlService->removeFromWhitelist($ip);
        }
        header('Location: /admin');
        exit;
    }
}