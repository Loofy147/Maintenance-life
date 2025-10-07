<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Event\SecurityThreatDetectedEvent;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Application\LoggerInterface;

/**
 * Provides security-related services like request validation, threat detection, and IP blocking.
 */
class SecurityService implements SecurityServiceInterface
{
    private ConfigurationInterface $config;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private array $blockedIPs = [];

    /**
     * SecurityService constructor.
     *
     * @param ConfigurationInterface   $config          The application configuration.
     * @param CacheInterface           $cache           The cache for storing security-related data.
     * @param LoggerInterface          $logger          The logger for recording security events.
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher for security events.
     */
    public function __construct(
        ConfigurationInterface $config,
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

    /**
     * Validates an incoming request against configured security rules.
     *
     * @return bool True if the request is valid, false otherwise.
     */
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

    /**
     * Detects potential security threats based on the request context.
     *
     * @param array<string, mixed> $context The request context to analyze.
     * @return array<int, array<string, mixed>> A list of detected threats.
     */
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

    /**
     * Adds an IP address to the blocklist.
     *
     * @param string $ip The IP address to block.
     */
    public function blockIP(string $ip): void
    {
        // Load the latest list, modify, and then save to prevent race conditions.
        $this->loadBlockedIPs();
        if (!in_array($ip, $this->blockedIPs, true)) {
            $this->blockedIPs[] = $ip;
            $this->cache->set('blocked_ips', $this->blockedIPs, 86400);
        }

        $this->logger->warning("IP blocked: {$ip}");
    }

    /**
     * Checks if an IP address is currently blocked.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is blocked, false otherwise.
     */
    public function isIPBlocked(string $ip): bool
    {
        // Always load the latest list from cache to prevent using stale data.
        $this->loadBlockedIPs();
        return in_array($ip, $this->blockedIPs, true);
    }

    /**
     * Loads the list of blocked IPs from the cache into memory.
     */
    private function loadBlockedIPs(): void
    {
        $this->blockedIPs = $this->cache->get('blocked_ips', []);
    }

    /**
     * Validates a CSRF token from the request against the one in the session.
     *
     * @return bool True if the token is valid, false otherwise.
     */
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

    /**
     * Checks if the current request is within the configured rate limits.
     *
     * @return bool True if the request is allowed, false if it's rate-limited.
     */
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

    /**
     * Performs a basic detection of SQL injection patterns in a string.
     *
     * @param string $input The string to inspect.
     * @return bool True if a potential SQL injection pattern is found, false otherwise.
     */
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

    /**
     * Performs a basic detection of Cross-Site Scripting (XSS) patterns in a string.
     *
     * @param string $input The string to inspect.
     * @return bool True if a potential XSS pattern is found, false otherwise.
     */
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

    /**
     * Performs a basic detection of brute-force login attacks for a given IP.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the number of login attempts exceeds the configured threshold.
     */
    private function detectBruteForce(string $ip): bool
    {
        $key = 'login_attempts_' . md5($ip);
        $attempts = $this->cache->get($key, 0);

        return $attempts > $this->config->get('security.max_login_attempts', 5);
    }
}