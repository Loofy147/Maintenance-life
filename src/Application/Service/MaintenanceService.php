<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Entity\MaintenanceSession;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Domain\ValueObject\TimePeriod;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

class MaintenanceService
{
    private ConfigurationInterface $config;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private MaintenanceStrategyInterface $strategy;
    private ?MaintenanceSession $currentSession = null;

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
     * Enable maintenance mode
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
     * Disable maintenance mode
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
     * Check if maintenance mode is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config->get('maintenance.enabled', false) === true;
    }

    /**
     * Check if request should be blocked
     */
    public function shouldBlock(array $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return !$this->strategy->shouldBypassMaintenance($context);
    }

    /**
     * Get current session
     */
    public function getCurrentSession(): ?MaintenanceSession
    {
        return $this->currentSession;
    }
}