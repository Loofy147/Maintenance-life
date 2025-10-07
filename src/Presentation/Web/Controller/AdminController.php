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
use OTPHP\TOTP;

/**
 * Handles all requests for the admin dashboard.
 *
 * This controller manages login, logout, 2FA, and all administrative actions
 * like enabling/disabling maintenance mode and managing the IP whitelist.
 */
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

    /**
     * AdminController constructor.
     *
     * @param TemplateRendererInterface $renderer              For rendering admin templates.
     * @param MaintenanceService        $maintenanceService    For managing maintenance mode.
     * @param AccessControlService      $accessControlService  For managing IP whitelists.
     * @param MetricsInterface          $metricsService        For retrieving performance metrics.
     * @param ConfigurationInterface    $config                For accessing application configuration.
     * @param HealthCheckAggregator     $healthCheckAggregator For running system health checks.
     * @param CircuitBreakerInterface   $circuitBreaker        For checking circuit breaker status.
     * @param AuthServiceInterface      $authService           For handling user authentication.
     * @param UserRepositoryInterface   $userRepository        For accessing user data.
     */
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

        // Ensure the default admin user exists for demo purposes.
        if ($this->userRepository->findByUsername('admin') === null) {
            $password = 'password';
            $user = new User('admin', password_hash($password, PASSWORD_DEFAULT));
            $this->userRepository->save($user);
        }
    }

    /**
     * Ensures that the user is logged in before allowing access to a method.
     * Redirects to the login page if the user is not authenticated.
     */
    private function loginRequired(): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Displays the admin login form.
     *
     * @return string The rendered HTML for the login page.
     */
    public function showLoginForm(): string
    {
        return $this->renderer->render('admin/login.phtml');
    }

    /**
     * Handles the login form submission.
     */
    public function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->authService->login($username, $password)) {
            $user = $this->userRepository->findByUsername($username);
            // Redirect to 2FA page if enabled, otherwise to the dashboard.
            if ($user && $user->isTwoFactorEnabled()) {
                header('Location: /admin/2fa');
            } else {
                header('Location: /admin');
            }
            exit;
        }

        // On failure, redirect back to login with an error.
        header('Location: /admin/login?error=1');
        exit;
    }

    /**
     * Displays the two-factor authentication form.
     *
     * @return string The rendered HTML for the 2FA page.
     */
    public function showTwoFactorForm(): string
    {
        if (!isset($_SESSION['2fa_user_id'])) {
            header('Location: /admin/login');
            exit;
        }
        return $this->renderer->render('admin/2fa.phtml');
    }

    /**
     * Handles the 2FA code verification.
     */
    public function verifyTwoFactor(): void
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;
        $code = $_POST['code'] ?? '';

        if ($userId === null) {
            header('Location: /admin/login');
            exit;
        }

        $user = $this->userRepository->findById((int)$userId);

        if ($user && $this->authService->verifyTwoFactorCode($user, $code)) {
            header('Location: /admin');
            exit;
        }

        header('Location: /admin/2fa?error=1');
        exit;
    }

    /**
     * Logs the user out and redirects to the login page.
     */
    public function logout(): void
    {
        $this->authService->logout();
        header('Location: /admin/login');
        exit;
    }

    /**
     * Displays the main admin dashboard.
     *
     * @return string The rendered HTML for the dashboard.
     */
    public function index(): string
    {
        $this->loginRequired();

        $user = $this->userRepository->findByUsername('admin');
        $qrCodeUrl = null;
        // Generate a QR code for 2FA setup if it's not already enabled.
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

    /**
     * Enables maintenance mode.
     */
    public function enableMaintenance(): void
    {
        $this->loginRequired();
        $reason = $_POST['reason'] ?? 'Enabled from admin dashboard';
        $this->maintenanceService->enable($reason);
        header('Location: /admin');
        exit;
    }

    /**
     * Disables maintenance mode.
     */
    public function disableMaintenance(): void
    {
        $this->loginRequired();
        $this->maintenanceService->disable();
        header('Location: /admin');
        exit;
    }

    /**
     * Adds an IP address to the whitelist.
     */
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

    /**
     * Removes an IP address from the whitelist.
     */
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