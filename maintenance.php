<?php
/**
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * ENTERPRISE MAINTENANCE MODE SYSTEM - PROFESSIONAL ARCHITECTURE
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * @package     MaintenancePro
 * @version     5.0.0 Enterprise Edition
 * @author      Enterprise Solutions Team
 * @license     Commercial License
 * @copyright   2025 Enterprise Solutions
 *
 * ARCHITECTURE PRINCIPLES:
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * 1. SOLID PRINCIPLES
 *    ├─ Single Responsibility: Each class has one clear purpose
 *    ├─ Open/Closed: Open for extension, closed for modification
 *    ├─ Liskov Substitution: Interface contracts strictly enforced
 *    ├─ Interface Segregation: Fine-grained, client-specific interfaces
 *    └─ Dependency Inversion: Depend on abstractions, not concretions
 *
 * 2. DESIGN PATTERNS
 *    ├─ Factory Pattern: Object creation abstraction
 *    ├─ Strategy Pattern: Algorithm encapsulation
 *    ├─ Observer Pattern: Event-driven architecture
 *    ├─ Decorator Pattern: Flexible feature extension
 *    ├─ Repository Pattern: Data access abstraction
 *    ├─ Service Layer Pattern: Business logic isolation
 *    ├─ Chain of Responsibility: Request processing pipeline
 *    └─ Dependency Injection: Inversion of Control container
 *
 * 3. ARCHITECTURAL LAYERS
 *    ├─ Domain Layer: Core business logic and entities
 *    ├─ Application Layer: Use cases and orchestration
 *    ├─ Infrastructure Layer: External services and persistence
 *    └─ Presentation Layer: UI and API endpoints
 *
 * 4. ENTERPRISE PATTERNS
 *    ├─ Domain-Driven Design (DDD)
 *    ├─ CQRS (Command Query Responsibility Segregation)
 *    ├─ Event Sourcing
 *    ├─ API Gateway Pattern
 *    └─ Circuit Breaker Pattern
 *
 * 5. QUALITY ATTRIBUTES
 *    ├─ Testability: 100% unit test coverage target
 *    ├─ Maintainability: Clean code, documentation, standards
 *    ├─ Scalability: Horizontal and vertical scaling support
 *    ├─ Security: Defense in depth, zero trust architecture
 *    ├─ Performance: Sub-100ms response time target
 *    └─ Observability: Comprehensive logging and monitoring
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 */

declare(strict_types=1);

namespace MaintenancePro;

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 1: CORE INTERFACES (Contracts)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Configuration Manager Interface
 * Defines contract for configuration management
 */
interface ConfigurationManagerInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function has(string $key): bool;
    public function load(string $path): void;
    public function save(string $path): void;
    public function all(): array;
}

/**
 * Cache Interface
 * Defines contract for caching operations
 */
interface CacheInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}

/**
 * Logger Interface
 * Defines contract for logging operations (PSR-3 compatible)
 */
interface LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function log(string $level, string $message, array $context = []): void;
}

/**
 * Repository Interface
 * Generic data access contract
 */
interface RepositoryInterface
{
    public function find(int $id);
    public function findAll(): array;
    public function findBy(array $criteria): array;
    public function save($entity): void;
    public function delete($entity): void;
}

/**
 * Event Dispatcher Interface
 * Event-driven architecture contract
 */
interface EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void;
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
    public function removeListener(string $eventName, callable $listener): void;
}

/**
 * Event Interface
 * Base contract for all events
 */
interface EventInterface
{
    public function getName(): string;
    public function getTimestamp(): int;
    public function getData(): array;
    public function isPropagationStopped(): bool;
    public function stopPropagation(): void;
}

/**
 * Maintenance Strategy Interface
 * Strategy pattern for maintenance decision logic
 */
interface MaintenanceStrategyInterface
{
    public function shouldEnterMaintenance(array $context): bool;
    public function shouldBypassMaintenance(array $context): bool;
    public function getMaintenanceDuration(): int;
}

/**
 * Authentication Interface
 * User authentication contract
 */
interface AuthenticationInterface
{
    public function authenticate(string $username, string $password): bool;
    public function isAuthenticated(): bool;
    public function getCurrentUser(): ?UserInterface;
    public function logout(): void;
}

/**
 * User Interface
 * User entity contract
 */
interface UserInterface
{
    public function getId(): int;
    public function getUsername(): string;
    public function getEmail(): string;
    public function getRoles(): array;
    public function hasRole(string $role): bool;
}

/**
 * Template Renderer Interface
 * Template rendering contract
 */
interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
    public function exists(string $template): bool;
}

/**
 * Notification Service Interface
 * Notification delivery contract
 */
interface NotificationServiceInterface
{
    public function send(NotificationInterface $notification): bool;
    public function sendAsync(NotificationInterface $notification): void;
}

/**
 * Notification Interface
 */
interface NotificationInterface
{
    public function getRecipients(): array;
    public function getSubject(): string;
    public function getMessage(): string;
    public function getChannel(): string;
}

/**
 * Analytics Service Interface
 */
interface AnalyticsServiceInterface
{
    public function track(string $event, array $properties = []): void;
    public function identify(string $userId, array $traits = []): void;
    public function getMetrics(string $metric, array $filters = []): array;
}

/**
 * Security Service Interface
 */
interface SecurityServiceInterface
{
    public function validateRequest(): bool;
    public function detectThreats(array $context): array;
    public function blockIP(string $ip): void;
    public function isIPBlocked(string $ip): bool;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 2: VALUE OBJECTS (Domain primitives)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * IP Address Value Object
 * Immutable representation of an IP address
 */
final class IPAddress
{
    private string $address;

    public function __construct(string $address)
    {
        if (!$this->isValid($address)) {
            throw new \InvalidArgumentException("Invalid IP address: {$address}");
        }
        $this->address = $address;
    }

    private function isValid(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false;
    }

    public function toString(): string
    {
        return $this->address;
    }

    public function isIPv4(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIPv6(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function equals(IPAddress $other): bool
    {
        return $this->address === $other->address;
    }

    public function inRange(string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $this->address === $cidr;
        }

        list($subnet, $bits) = explode('/', $cidr);

        if ($this->isIPv6()) {
            return $this->ipv6InRange($subnet, (int)$bits);
        }

        $ip = ip2long($this->address);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);

        return ($ip & $mask) === ($subnet & $mask);
    }

    private function ipv6InRange(string $subnet, int $bits): bool
    {
        $ip = inet_pton($this->address);
        $subnet = inet_pton($subnet);

        $bytes = intval($bits / 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ip, 0, $bytes) !== substr($subnet, 0, $bytes)) {
            return false;
        }

        if ($remainder > 0) {
            $mask = 0xFF << (8 - $remainder);
            return (ord($ip[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask);
        }

        return true;
    }
}

/**
 * Time Period Value Object
 */
final class TimePeriod
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start time must be before end time');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function contains(\DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime <= $this->end;
    }

    public function getDuration(): \DateInterval
    {
        return $this->start->diff($this->end);
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }
}

/**
 * Email Value Object
 */
final class Email
{
    private string $email;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
        $this->email = strtolower($email);
    }

    public function toString(): string
    {
        return $this->email;
    }

    public function getDomain(): string
    {
        return substr($this->email, strpos($this->email, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->email, 0, strpos($this->email, '@'));
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 3: DOMAIN ENTITIES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Maintenance Session Entity
 * Represents a maintenance session with full lifecycle
 */
class MaintenanceSession
{
    private ?int $id = null;
    private MaintenanceStatus $status;
    private TimePeriod $period;
    private string $reason;
    private array $metadata;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        TimePeriod $period,
        string $reason,
        array $metadata = []
    ) {
        $this->period = $period;
        $this->reason = $reason;
        $this->metadata = $metadata;
        $this->status = MaintenanceStatus::SCHEDULED;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function start(): void
    {
        if ($this->status !== MaintenanceStatus::SCHEDULED) {
            throw new \LogicException('Can only start a scheduled maintenance session');
        }

        $this->status = MaintenanceStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== MaintenanceStatus::ACTIVE) {
            throw new \LogicException('Can only complete an active maintenance session');
        }

        $this->status = MaintenanceStatus::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === MaintenanceStatus::COMPLETED) {
            throw new \LogicException('Cannot cancel a completed maintenance session');
        }

        $this->status = MaintenanceStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->status === MaintenanceStatus::ACTIVE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): MaintenanceStatus
    {
        return $this->status;
    }

    public function getPeriod(): TimePeriod
    {
        return $this->period;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}

/**
 * Maintenance Status Enum
 */
enum MaintenanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

/**
 * User Entity
 */
class User implements UserInterface
{
    private int $id;
    private string $username;
    private Email $email;
    private string $passwordHash;
    private array $roles;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $id,
        string $username,
        Email $email,
        string $passwordHash,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email->toString();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }
}

/**
 * Analytics Event Entity
 */
class AnalyticsEvent
{
    private ?int $id = null;
    private string $eventType;
    private array $properties;
    private IPAddress $ipAddress;
    private string $userAgent;
    private \DateTimeImmutable $timestamp;

    public function __construct(
        string $eventType,
        array $properties,
        IPAddress $ipAddress,
        string $userAgent
    ) {
        $this->eventType = $eventType;
        $this->properties = $properties;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getIPAddress(): IPAddress
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 4: DOMAIN EVENTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Base Event Class
 */
abstract class BaseEvent implements EventInterface
{
    private string $name;
    private int $timestamp;
    private array $data;
    private bool $propagationStopped = false;

    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->timestamp = time();
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

/**
 * Maintenance Enabled Event
 */
class MaintenanceEnabledEvent extends BaseEvent
{
    public function __construct(MaintenanceSession $session)
    {
        parent::__construct('maintenance.enabled', [
            'session_id' => $session->getId(),
            'reason' => $session->getReason(),
            'period' => [
                'start' => $session->getPeriod()->getStart()->format('c'),
                'end' => $session->getPeriod()->getEnd()->format('c'),
            ]
        ]);
    }
}

/**
 * Maintenance Disabled Event
 */
class MaintenanceDisabledEvent extends BaseEvent
{
    public function __construct(MaintenanceSession $session)
    {
        parent::__construct('maintenance.disabled', [
            'session_id' => $session->getId(),
            'completed_at' => (new \DateTimeImmutable())->format('c')
        ]);
    }
}

/**
 * Request Blocked Event
 */
class RequestBlockedEvent extends BaseEvent
{
    public function __construct(IPAddress $ipAddress, string $reason)
    {
        parent::__construct('request.blocked', [
            'ip' => $ipAddress->toString(),
            'reason' => $reason
        ]);
    }
}

/**
 * Security Threat Detected Event
 */
class SecurityThreatDetectedEvent extends BaseEvent
{
    public function __construct(string $threatType, array $details)
    {
        parent::__construct('security.threat_detected', [
            'threat_type' => $threatType,
            'details' => $details
        ]);
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 8: DEPENDENCY INJECTION CONTAINER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Service Container (Dependency Injection Container)
 * Implements Inversion of Control
 */
class ServiceContainer
{
    private array $services = [];
    private array $factories = [];
    private array $instances = [];

    /**
     * Register a service factory
     */
    public function register(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    /**
     * Register a singleton service
     */
    public function singleton(string $name, callable $factory): void
    {
        $this->register($name, function() use ($name, $factory) {
            if (!isset($this->instances[$name])) {
                $this->instances[$name] = $factory($this);
            }
            return $this->instances[$name];
        });
    }

    /**
     * Register an existing instance
     */
    public function instance(string $name, $instance): void
    {
        $this->instances[$name] = $instance;
    }

    /**
     * Get a service from the container
     */
    public function get(string $name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->factories[$name])) {
            throw new \RuntimeException("Service not found: {$name}");
        }

        return $this->factories[$name]($this);
    }

    /**
     * Check if service exists
     */
    public function has(string $name): bool
    {
        return isset($this->factories[$name]) || isset($this->instances[$name]);
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 5: INFRASTRUCTURE IMPLEMENTATIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// NOTE: The BasicTemplateRenderer is still a basic placeholder.
// In a real-world application, it would be more robust.

/**
 * JSON Configuration Manager
 * Implements configuration management with file persistence
 */
class JsonConfigurationManager implements ConfigurationManagerInterface
{
    private array $config = [];
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (file_exists($filePath)) {
            $this->load($filePath);
        }
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        $content = file_get_contents($path);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in configuration file: ' . json_last_error_msg());
        }

        $this->config = $config;
    }

    public function save(string $path): void
    {
        $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Failed to save configuration to: {$path}");
        }
    }

    public function all(): array
    {
        return $this->config;
    }
}

/**
 * File System Cache Implementation
 */
class FileSystemCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}

/**
 * File Logger Implementation (PSR-3 compatible)
 */
class FileLogger implements LoggerInterface
{
    private string $logFile;
    private string $dateFormat = 'Y-m-d H:i:s';

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date($this->dateFormat);
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Event Dispatcher Implementation
 */
class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getName();

        $this->logger->debug("Dispatching event: {$eventName}", $event->getData());

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        // Sort listeners by priority (higher first)
        uasort($this->listeners[$eventName], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        foreach ($this->listeners[$eventName] as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            try {
                call_user_func($listener['callback'], $event);
            } catch (\Exception $e) {
                $this->logger->error("Error in event listener", [
                    'event' => $eventName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = [
            'callback' => $listener,
            'priority' => $priority
        ];
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $key => $listenerData) {
            if ($listenerData['callback'] === $listener) {
                unset($this->listeners[$eventName][$key]);
            }
        }
    }
}

class BasicTemplateRenderer implements TemplateRendererInterface
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

    public function render(string $template, array $data = []): string
    {
        $path = $this->templateDir . '/' . $template;
        if (!$this->exists($template)) {
            throw new \Exception("Template not found: {$template}");
        }
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function exists(string $template): bool
    {
        return file_exists($this->templateDir . '/' . $template);
    }
}

/**
 * SQLite Repository Base Class
 */
abstract class SQLiteRepository implements RepositoryInterface
{
    protected \PDO $db;
    protected string $table;
    protected LoggerInterface $logger;

    public function __construct(\PDO $db, string $table, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->table = $table;
        $this->logger = $logger;
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        $results = [];

        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($data);
        }

        return $results;
    }

    public function findBy(array $criteria): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $where = implode(' AND ', $conditions);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);

        $results = [];
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($data);
        }

        return $results;
    }

    public function save($entity): void
    {
        $data = $this->extract($entity);

        if (isset($data['id']) && $data['id']) {
            $this->update($data);
        } else {
            $this->insert($data);
        }
    }

    public function delete($entity): void
    {
        $data = $this->extract($entity);

        if (!isset($data['id'])) {
            throw new \LogicException('Cannot delete entity without ID');
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
    }

    protected function insert(array $data): void
    {
        unset($data['id']);

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    protected function update(array $data): void
    {
        $id = $data['id'];
        unset($data['id']);

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    abstract protected function hydrate(array $data);
    abstract protected function extract($entity): array;
}

/**
 * Concrete implementation of the repository for Analytics Events
 */
class AnalyticsEventRepository extends SQLiteRepository
{
    public function __construct(\PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, 'analytics_events', $logger);
    }

    protected function hydrate(array $data)
    {
        // In a real application, this would reconstruct the full entity
        return new AnalyticsEvent(
            $data['event_type'],
            json_decode($data['properties'], true),
            new IPAddress($data['ip_address']),
            $data['user_agent']
        );
    }

    protected function extract($entity): array
    {
        if (!$entity instanceof AnalyticsEvent) {
            throw new \InvalidArgumentException('Entity must be an instance of AnalyticsEvent');
        }

        return [
            'event_type' => $entity->getEventType(),
            'properties' => json_encode($entity->getProperties()),
            'ip_address' => $entity->getIPAddress()->toString(),
            'user_agent' => $entity->getUserAgent(),
            'timestamp' => $entity->getTimestamp()->format('Y-m-d H:i:s'),
        ];
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 6: APPLICATION SERVICES (Business Logic Layer)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Maintenance Service
 * Orchestrates maintenance mode operations
 */
class MaintenanceService
{
    private ConfigurationManagerInterface $config;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private MaintenanceStrategyInterface $strategy;
    private ?MaintenanceSession $currentSession = null;

    public function __construct(
        ConfigurationManagerInterface $config,
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
        $this->config->save($this->config->get('system.config_path'));

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
        $this->config->save($this->config->get('system.config_path'));

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

/**
 * Access Control Service
 * Manages IP whitelisting and bypass rules
 */
class AccessControlService
{
    private ConfigurationManagerInterface $config;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ConfigurationManagerInterface $config,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIPWhitelisted(IPAddress $ip): bool
    {
        $cacheKey = 'whitelist_check_' . md5($ip->toString());

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $whitelist = $this->config->get('access.whitelist.ips', []);

        foreach ($whitelist as $allowedIP) {
            if ($ip->inRange($allowedIP)) {
                $this->cache->set($cacheKey, true, 300);
                return true;
            }
        }

        $this->cache->set($cacheKey, false, 300);
        return false;
    }

    /**
     * Add IP to whitelist
     */
    public function addToWhitelist(string $ip): void
    {
        $ipObject = new IPAddress($ip);

        $whitelist = $this->config->get('access.whitelist.ips', []);

        if (!in_array($ip, $whitelist, true)) {
            $whitelist[] = $ip;
            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save($this->config->get('system.config_path'));

            $this->logger->info("IP added to whitelist: {$ip}");
        }
    }

    /**
     * Remove IP from whitelist
     */
    public function removeFromWhitelist(string $ip): void
    {
        $whitelist = $this->config->get('access.whitelist.ips', []);

        $key = array_search($ip, $whitelist, true);
        if ($key !== false) {
            unset($whitelist[$key]);
            $whitelist = array_values($whitelist);

            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save($this->config->get('system.config_path'));

            $this->logger->info("IP removed from whitelist: {$ip}");
        }
    }

    /**
     * Check if access key is valid
     */
    public function isValidAccessKey(string $key): bool
    {
        $validKeys = $this->config->get('access.access_keys', []);
        return in_array($key, $validKeys, true);
    }
}

/**
 * Security Service Implementation
 */
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

/**
 * Analytics Service Implementation
 */
class AnalyticsService implements AnalyticsServiceInterface
{
    private RepositoryInterface $eventRepository;
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(
        RepositoryInterface $eventRepository,
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function track(string $event, array $properties = []): void
    {
        try {
            $ip = new IPAddress($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $analyticsEvent = new AnalyticsEvent($event, $properties, $ip, $userAgent);
            $this->eventRepository->save($analyticsEvent);

            $this->logger->debug("Analytics event tracked: {$event}");
        } catch (\Exception $e) {
            $this->logger->error("Failed to track analytics event", [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function identify(string $userId, array $traits = []): void
    {
        $this->cache->set("user_traits_{$userId}", $traits, 86400);
    }

    public function getMetrics(string $metric, array $filters = []): array
    {
        $cacheKey = 'metrics_' . md5($metric . json_encode($filters));

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Implement metric calculation based on stored events
        $metrics = $this->calculateMetrics($metric, $filters);

        $this->cache->set($cacheKey, $metrics, 300);

        return $metrics;
    }

    private function calculateMetrics(string $metric, array $filters): array
    {
        // Simplified metric calculation
        return [
            'metric' => $metric,
            'value' => 0,
            'timestamp' => time()
        ];
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 7: MAINTENANCE STRATEGIES (Strategy Pattern)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Default Maintenance Strategy
 */
class DefaultMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationManagerInterface $config;
    private AccessControlService $accessControl;

    public function __construct(
        ConfigurationManagerInterface $config,
        AccessControlService $accessControl
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
    }

    public function shouldEnterMaintenance(array $context): bool
    {
        // Check if manually enabled
        if ($this->config->get('maintenance.manual_mode', false)) {
            return true;
        }

        // Check scheduled maintenance
        if ($this->config->get('maintenance.scheduled.enabled', false)) {
            return $this->isInMaintenanceWindow();
        }

        return false;
    }

    public function shouldBypassMaintenance(array $context): bool
    {
        // Check IP whitelist
        if (isset($context['ip'])) {
            try {
                $ip = new IPAddress($context['ip']);
                if ($this->accessControl->isIPWhitelisted($ip)) {
                    return true;
                }
            } catch (\Exception $e) {
                // Invalid IP, don't bypass
            }
        }

        // Check access key
        if (isset($context['access_key'])) {
            if ($this->accessControl->isValidAccessKey($context['access_key'])) {
                return true;
            }
        }

        // Check user role
        if (isset($context['user']) && $context['user'] instanceof UserInterface) {
            if ($context['user']->hasRole('ROLE_ADMIN')) {
                return true;
            }
        }

        return false;
    }

    public function getMaintenanceDuration(): int
    {
        return $this->config->get('maintenance.default_duration', 3600);
    }

    private function isInMaintenanceWindow(): bool
    {
        $now = new \DateTimeImmutable();
        $start = $this->config->get('maintenance.scheduled.start_time');
        $end = $this->config->get('maintenance.scheduled.end_time');

        if (!$start || !$end) {
            return false;
        }

        try {
            $startTime = new \DateTimeImmutable($start);
            $endTime = new \DateTimeImmutable($end);
            $period = new TimePeriod($startTime, $endTime);

            return $period->contains($now);
        } catch (\Exception $e) {
            return false;
        }
    }
}

/**
 * Intelligent Maintenance Strategy (AI-powered)
 */
class IntelligentMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationManagerInterface $config;
    private AccessControlService $accessControl;
    private AnalyticsServiceInterface $analytics;

    public function __construct(
        ConfigurationManagerInterface $config,
        AccessControlService $accessControl,
        AnalyticsServiceInterface $analytics
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
        $this->analytics = $analytics;
    }

    public function shouldEnterMaintenance(array $context): bool
    {
        // Use AI/ML to determine optimal maintenance timing
        $metrics = $this->analytics->getMetrics('traffic', ['period' => 'last_hour']);

        // Enter maintenance if traffic is low
        if (isset($metrics['value']) && $metrics['value'] < 100) {
            return true;
        }

        // Check system health indicators
        if (isset($context['error_rate']) && $context['error_rate'] > 0.05) {
            return true;
        }

        return false;
    }

    public function shouldBypassMaintenance(array $context): bool
    {
        // First check standard bypass rules
        $strategy = new DefaultMaintenanceStrategy($this->config, $this->accessControl);
        if ($strategy->shouldBypassMaintenance($context)) {
            return true;
        }

        // AI-powered user segmentation
        if (isset($context['user_segment'])) {
            $prioritySegments = $this->config->get('ai.priority_user_segments', []);
            if (in_array($context['user_segment'], $prioritySegments, true)) {
                return true;
            }
        }

        return false;
    }

    public function getMaintenanceDuration(): int
    {
        // Predict optimal duration based on historical data
        $metrics = $this->analytics->getMetrics('maintenance_duration', ['limit' => 10]);

        // Use average of last 10 maintenance sessions
        if (!empty($metrics)) {
            $sum = array_sum(array_column($metrics, 'value'));
            return (int)($sum / count($metrics));
        }

        return 3600; // Default 1 hour
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 9: APPLICATION BOOTSTRAP & INITIALIZATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Application Bootstrap
 * Initializes and wires all dependencies
 */
class Application
{
    private ServiceContainer $container;
    private ConfigurationManagerInterface $config;
    private LoggerInterface $logger;

    public function __construct(string $rootPath)
    {
        $this->container = new ServiceContainer();
        $this->setupPaths($rootPath);
        $this->registerServices();
        $this->initialize();
    }

    /**
     * Setup file system paths
     */
    private function setupPaths(string $rootPath): void
    {
        $paths = [
            'root' => $rootPath,
            'config' => $rootPath . '/maintenance-pro/config',
            'cache' => $rootPath . '/maintenance-pro/cache',
            'logs' => $rootPath . '/maintenance-pro/logs',
            'templates' => $rootPath . '/maintenance-pro/templates',
            'storage' => $rootPath . '/maintenance-pro/storage',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $this->container->instance('paths', $paths);
    }

    /**
     * Register all services in the container
     */
    private function registerServices(): void
    {
        $paths = $this->container->get('paths');

        // Configuration Manager (Singleton)
        $this->container->singleton('config', function($c) use ($paths) {
            $configPath = $paths['config'] . '/config.json';
            $config = new JsonConfigurationManager($configPath);

            // Set system paths in config
            $config->set('system.config_path', $configPath);
            $config->set('system.paths', $paths);

            return $config;
        });

        // Logger (Singleton)
        $this->container->singleton('logger', function($c) use ($paths) {
            return new FileLogger($paths['logs'] . '/app.log');
        });

        // Cache (Singleton)
        $this->container->singleton('cache', function($c) use ($paths) {
            return new FileSystemCache($paths['cache']);
        });

        // Event Dispatcher
        $this->container->singleton(EventDispatcherInterface::class, function($c) {
            return new EventDispatcher($c->get('logger'));
        });

        // Template Renderer
        $this->container->singleton(TemplateRendererInterface::class, function($c) use ($paths) {
            return new BasicTemplateRenderer($paths['templates']);
        });

        // Database (PDO for SQLite)
        $this->container->singleton(\PDO::class, function($c) use ($paths) {
            $storagePath = $paths['storage'] . '/database.sqlite';
            $pdo = new \PDO('sqlite:' . $storagePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });

        // Analytics Event Repository
        $this->container->singleton(RepositoryInterface::class, function($c) {
            return new AnalyticsEventRepository($c->get(\PDO::class), $c->get('logger'));
        });

        // Core Application Services
        $this->container->singleton(AccessControlService::class, function($c) {
            return new AccessControlService($c->get('config'), $c->get('cache'), $c->get('logger'));
        });

        $this->container->singleton(AnalyticsServiceInterface::class, function($c) {
            return new AnalyticsService(
                $c->get(RepositoryInterface::class),
                $c->get('logger'),
                $c->get('cache')
            );
        });

        $this->container->singleton(SecurityServiceInterface::class, function($c) {
            return new SecurityService(
                $c->get('config'),
                $c->get('cache'),
                $c->get('logger'),
                $c->get(EventDispatcherInterface::class)
            );
        });

        // Maintenance Strategy
        $this->container->singleton(MaintenanceStrategyInterface::class, function($c) {
            // Can be switched to IntelligentMaintenanceStrategy based on config
            return new DefaultMaintenanceStrategy(
                $c->get('config'),
                $c->get(AccessControlService::class)
            );
        });

        // Maintenance Service
        $this->container->singleton(MaintenanceService::class, function($c) {
            return new MaintenanceService(
                $c->get('config'),
                $c->get(EventDispatcherInterface::class),
                $c->get('logger'),
                $c->get(MaintenanceStrategyInterface::class)
            );
        });
    }

    /**
     * Initialize core application components
     */
    private function initialize(): void
    {
        $this->config = $this->container->get('config');
        $this->logger = $this->container->get('logger');

        // Set timezone and error handling
        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        $this->logger->info('Application started.');

        // This is where you would typically handle a request and dispatch to a controller
        // For this example, we'll just output a message.
        echo "Application is running.";

        $this->logger->info('Application finished.');
    }

    /**
     * Custom error handler
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $this->logger->error("Error: [$errno] $errstr in $errfile on line $errline");
    }

    /**
     * Custom exception handler
     */
    public function handleException(\Throwable $e): void
    {
        $this->logger->critical(
            "Uncaught Exception: " . $e->getMessage(),
            ['exception' => $e]
        );
    }

    /**
     * Get the service container
     */
    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }
}
