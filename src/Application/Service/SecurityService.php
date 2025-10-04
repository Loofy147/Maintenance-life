<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Event\SecurityThreatDetectedEvent;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Infrastructure\Cache\CacheInterface;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

class SecurityService implements SecurityServiceInterface
{
    private ConfigurationManagerInterface $config;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private array $blockedIPs = [];

    public function __construct(
        ConfigurationManagerInterface $config,
        CacheInterface $cache,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;

        $this->loadBlockedIPs();
    }

    public function validateRequest(): bool
    {
        // CSRF protection
        if ($this->config->get('security.csrf_protection', false)) {
            if (!$this->validateCSRFToken()) {
                return false;
            }
        }

        // Rate limiting
        if ($this->config->get('security.rate_limiting.enabled', false)) {
            if (!$this->checkRateLimit()) {
                return false;
            }
        }

        return true;
    }

    public function detectThreats(array $context): array
    {
        $threats = [];

        // SQL Injection detection
        if (isset($context['query_string'])) {
            if ($this->detectSQLInjection($context['query_string'])) {
                $threats[] = [
                    'type' => 'sql_injection',
                    'severity' => 'high',
                    'details' => 'Potential SQL injection detected'
                ];
            }
        }

        // XSS detection
        if (isset($context['user_input'])) {
            if ($this->detectXSS($context['user_input'])) {
                $threats[] = [
                    'type' => 'xss',
                    'severity' => 'high',
                    'details' => 'Potential XSS attack detected'
                ];
            }
        }

        // Brute force detection
        if (isset($context['ip'])) {
            if ($this->detectBruteForce($context['ip'])) {
                $threats[] = [
                    'type' => 'brute_force',
                    'severity' => 'medium',
                    'details' => 'Potential brute force attack detected'
                ];
            }
        }

        if (!empty($threats)) {
            $event = new SecurityThreatDetectedEvent('multiple', $threats);
            $this->eventDispatcher->dispatch($event);
        }

        return $threats;
    }

    public function blockIP(string $ip): void
    {
        $this->blockedIPs[] = $ip;
        $this->cache->set('blocked_ips', $this->blockedIPs, 86400);

        $this->logger->warning("IP blocked: {$ip}");
    }

    public function isIPBlocked(string $ip): bool
    {
        return in_array($ip, $this->blockedIPs, true);
    }

    private function loadBlockedIPs(): void
    {
        $this->blockedIPs = $this->cache->get('blocked_ips', []);
    }

    private function validateCSRFToken(): bool
    {
        // Simplified CSRF validation
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token) {
            return false;
        }

        $validToken = $this->cache->get('csrf_token_' . session_id());
        return $token === $validToken;
    }

    private function checkRateLimit(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . md5($ip);

        $requests = $this->cache->get($key, 0);
        $maxRequests = $this->config->get('security.rate_limiting.max_requests', 100);

        if ($requests >= $maxRequests) {
            $this->logger->warning("Rate limit exceeded for IP: {$ip}");
            return false;
        }

        $this->cache->set($key, $requests + 1, 3600);
        return true;
    }

    private function detectSQLInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(;|\-\-|\/\*|\*\/)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function detectXSS(string $input): bool
    {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function detectBruteForce(string $ip): bool
    {
        $key = 'login_attempts_' . md5($ip);
        $attempts = $this->cache->get($key, 0);

        return $attempts > $this->config->get('security.max_login_attempts', 5);
    }
}