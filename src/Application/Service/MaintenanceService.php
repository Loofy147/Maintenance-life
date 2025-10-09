<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Entity\MaintenanceSession;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;
use MaintenancePro\Domain\Contracts\MaintenanceServiceInterface;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Domain\ValueObjects\TimePeriod;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Application\LoggerInterface;

/**
 * Manages the application's maintenance mode state.
 *
 * This service provides methods to enable and disable maintenance mode, check its
 * status, and determine if incoming requests should be blocked.
 */
class MaintenanceService implements MaintenanceServiceInterface
{
    private ConfigurationInterface $config;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private MaintenanceStrategyInterface $strategy;
    private ?MaintenanceSession $currentSession = null;

    /**
     * MaintenanceService constructor.
     *
     * @param ConfigurationInterface       $config          The application configuration.
     * @param EventDispatcherInterface     $eventDispatcher The event dispatcher for maintenance events.
     * @param LoggerInterface              $logger          The logger for recording maintenance state changes.
     * @param MaintenanceStrategyInterface $strategy        The strategy for determining maintenance mode behavior.
     */
    public function __construct(
        ConfigurationInterface $config,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        MaintenanceStrategyInterface $strategy
    ) {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->strategy = $strategy;
    }

    /**
     * Enables maintenance mode for a specified duration.
     *
     * @param string                    $reason  The reason for enabling maintenance mode.
     * @param \DateTimeImmutable|null $endTime The time when maintenance mode should end. Defaults to 1 hour from now.
     * @return MaintenanceSession The created maintenance session.
     * @throws \LogicException If maintenance mode is already enabled.
     */
    public function enable(string $reason, ?\DateTimeImmutable $endTime = null): MaintenanceSession
    {
        if ($this->isEnabled()) {
            throw new \LogicException('Maintenance mode is already enabled');
        }

        $startTime = new \DateTimeImmutable();
        $endTime = $endTime ?? $startTime->modify('+1 hour');

        $period = new TimePeriod($startTime, $endTime);
        $session = new MaintenanceSession($period, $reason);
        $session->start();

        $this->currentSession = $session;
        $this->config->set('maintenance.enabled', true);
        $this->config->set('maintenance.session_id', $session->getId());
        $this->config->set('maintenance.reason', $reason);
        $this->config->set('maintenance.scheduled_at', $endTime->format('c'));
        $this->config->save();

        $event = new MaintenanceEnabledEvent($session);
        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Maintenance mode enabled', [
            'reason' => $reason,
            'end_time' => $endTime->format('c')
        ]);

        return $session;
    }

    /**
     * Disables maintenance mode.
     *
     * @throws \LogicException If maintenance mode is not currently enabled.
     */
    public function disable(): void
    {
        if (!$this->isEnabled()) {
            throw new \LogicException('Maintenance mode is not enabled');
        }

        if ($this->currentSession) {
            $this->currentSession->complete();
        }

        $this->config->set('maintenance.enabled', false);
        $this->config->set('maintenance.session_id', null);
        $this->config->save();

        if ($this->currentSession) {
            $event = new MaintenanceDisabledEvent($this->currentSession);
            $this->eventDispatcher->dispatch($event);
        }

        $this->logger->info('Maintenance mode disabled');

        $this->currentSession = null;
    }

    /**
     * Checks if maintenance mode is currently enabled in the configuration.
     *
     * @return bool True if maintenance mode is enabled, false otherwise.
     */
    public function isEnabled(): bool
    {
        return $this->config->get('maintenance.enabled', false) === true;
    }

    /**
     * Determines whether an incoming request should be blocked based on the current maintenance state and strategy.
     *
     * @param array<string, mixed> $context The request context (e.g., IP address, user role).
     * @return bool True if the request should be blocked, false otherwise.
     */
    public function shouldBlock(array $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return !$this->strategy->shouldBypassMaintenance($context);
    }

    /**
     * Gets the current active maintenance session.
     *
     * @return MaintenanceSession|null The current session, or null if none is active.
     */
    public function getCurrentSession(): ?MaintenanceSession
    {
        return $this->currentSession;
    }

    public function getStatus(): array
    {
        return [
            'is_active' => $this->isEnabled(),
            'reason' => $this->config->get('maintenance.reason'),
            'scheduled_at' => $this->config->get('maintenance.scheduled_at'),
        ];
    }

    public function addWhitelistedIp(string $ip): void
    {
        $whitelistedIps = $this->config->get('access.whitelist.ips', []);
        if (!in_array($ip, $whitelistedIps)) {
            $whitelistedIps[] = $ip;
            $this->config->set('access.whitelist.ips', $whitelistedIps);
            $this->config->save();
        }
    }

    public function removeWhitelistedIp(string $ip): void
    {
        $whitelistedIps = $this->config->get('access.whitelist.ips', []);
        $whitelistedIps = array_filter($whitelistedIps, fn($whitelistedIp) => $whitelistedIp !== $ip);
        $this->config->set('access.whitelist.ips', array_values($whitelistedIps));
        $this->config->save();
    }
}