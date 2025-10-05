<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Web\Controller;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\Contract\AuthServiceInterface;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Entity\User;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;
use SpomkyLabs\OTPHP\TOTP;

class AdminController
{
    private TemplateRendererInterface $renderer;
    private MaintenanceService $maintenanceService;
    private AccessControlService $accessControlService;
    private MetricsInterface $metricsService;
    private ConfigurationInterface $config;
    private HealthCheckAggregator $healthCheckAggregator;
    private CircuitBreakerInterface $circuitBreaker;
    private AuthServiceInterface $authService;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        TemplateRendererInterface $renderer,
        MaintenanceService $maintenanceService,
        AccessControlService $accessControlService,
        MetricsInterface $metricsService,
        ConfigurationInterface $config,
        HealthCheckAggregator $healthCheckAggregator,
        CircuitBreakerInterface $circuitBreaker,
        AuthServiceInterface $authService,
        UserRepositoryInterface $userRepository
    ) {
        $this->renderer = $renderer;
        $this->maintenanceService = $maintenanceService;
        $this->accessControlService = $accessControlService;
        $this->metricsService = $metricsService;
        $this->config = $config;
        $this->healthCheckAggregator = $healthCheckAggregator;
        $this->circuitBreaker = $circuitBreaker;
        $this->authService = $authService;
        $this->userRepository = $userRepository;

        if ($this->userRepository->findByUsername('admin') === null) {
            $password = 'password';
            $user = new User('admin', password_hash($password, PASSWORD_DEFAULT));
            $this->userRepository->save($user);
        }
    }

    private function loginRequired(): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function showLoginForm(): string
    {
        return $this->renderer->render('admin/login.phtml');
    }

    public function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->authService->login($username, $password)) {
            $user = $this->userRepository->findByUsername($username);
            if ($user && $user->isTwoFactorEnabled()) {
                header('Location: /admin/2fa');
            } else {
                header('Location: /admin');
            }
            exit;
        }

        header('Location: /admin/login?error=1');
        exit;
    }

    public function showTwoFactorForm(): string
    {
        if (!isset($_SESSION['2fa_user_id'])) {
            header('Location: /admin/login');
            exit;
        }
        return $this->renderer->render('admin/2fa.phtml');
    }

    public function verifyTwoFactor(): void
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;
        $code = $_POST['code'] ?? '';

        // A findById method would be better here.
        $user = $this->userRepository->findByUsername('admin');

        if ($user && $this->authService->verifyTwoFactorCode($user, $code)) {
            header('Location: /admin');
            exit;
        }

        header('Location: /admin/2fa?error=1');
        exit;
    }

    public function logout(): void
    {
        $this->authService->logout();
        header('Location: /admin/login');
        exit;
    }

    public function index(): string
    {
        $this->loginRequired();

        $user = $this->userRepository->findByUsername('admin');
        $qrCodeUrl = null;
        if ($user && !$user->isTwoFactorEnabled()) {
            $secret = $this->authService->generateTwoFactorSecret($user);
            $totp = TOTP::createFromSecret($secret);
            $totp->setLabel($user->getUsername());
            $totp->setIssuer($this->config->get('2fa.issuer', 'MaintenancePro'));
            $qrCodeUrl = $totp->getProvisioningUri();
        }

        $data = [
            'title' => 'Admin Dashboard',
            'maintenance_status' => $this->maintenanceService->isEnabled(),
            'config' => $this->config->all(),
            'metrics' => $this->metricsService->getReport(),
            'health_report' => $this->healthCheckAggregator->runAll(),
            'circuit_breaker_status' => $this->circuitBreaker->getStatus('mock_external_service'),
            'user' => $user,
            'qr_code_url' => $qrCodeUrl,
        ];
        return $this->renderer->render('admin/dashboard.phtml', $data);
    }

    public function enableMaintenance(): void
    {
        $this->loginRequired();
        $reason = $_POST['reason'] ?? 'Enabled from admin dashboard';
        $this->maintenanceService->enable($reason);
        header('Location: /admin');
        exit;
    }

    public function disableMaintenance(): void
    {
        $this->loginRequired();
        $this->maintenanceService->disable();
        header('Location: /admin');
        exit;
    }

    public function addWhitelistIp(): void
    {
        $this->loginRequired();
        $ip = $_POST['ip'] ?? null;
        if ($ip) {
            $this->accessControlService->addToWhitelist($ip);
        }
        header('Location: /admin');
        exit;
    }

    public function removeWhitelistIp(): void
    {
        $this->loginRequired();
        $ip = $_POST['ip'] ?? null;
        if ($ip) {
            $this->accessControlService->removeFromWhitelist($ip);
        }
        header('Location: /admin');
        exit;
    }
}