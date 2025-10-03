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
                'start' => $session->getPeriod()->getStart()->format(\DateTimeInterface::ISO8601),
                'end' => $session->getPeriod()->getEnd()->format(\DateTimeInterface::ISO8601),
            ],
        ]);
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
            return new EventDispatcher();
        });

        // Template Renderer
        $this->container->singleton(TemplateRendererInterface::class, function($c) use ($paths) {
            return new BasicTemplateRenderer($paths['templates']);
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

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SECTION 10: INFRASTRUCTURE & PLACEHOLDER IMPLEMENTATIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// NOTE: These are basic implementations for demonstration purposes.
// In a real-world application, these would be more robust and likely
// come from established libraries (e.g., Monolog, Pimple/PHP-DI, Flysystem).

class ServiceContainer
{
    private array $instances = [];
    private array $bindings = [];

    public function instance(string $key, $instance): void
    {
        $this->instances[$key] = $instance;
    }

    public function singleton(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    public function get(string $key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            $this->instances[$key] = $this->bindings[$key]($this);
            return $this->instances[$key];
        }

        throw new \Exception("Service not found: {$key}");
    }
}

class JsonConfigurationManager implements ConfigurationManagerInterface
{
    private array $config = [];
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->load($path);
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
        $temp = &$this->config;
        foreach ($keys as $k) {
            if (!isset($temp[$k]) || !is_array($temp[$k])) {
                $temp[$k] = [];
            }
            $temp = &$temp[$k];
        }
        $temp = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function load(string $path): void
    {
        if (file_exists($path)) {
            $this->config = json_decode(file_get_contents($path), true) ?: [];
        }
    }

    public function save(string $path): void
    {
        file_put_contents($path, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function all(): array
    {
        return $this->config;
    }
}

class FileLogger implements LoggerInterface
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logEntry = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            json_encode($context)
        );
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function emergency(string $message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log('alert', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('critical', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('error', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('warning', $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log('notice', $message, $context); }
    public function info(string $message, array $context = []): void { $this->log('info', $message, $context); }
    public function debug(string $message, array $context = []): void { $this->log('debug', $message, $context); }
}

class FileSystemCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function get(string $key, $default = null)
    {
        $path = $this->getPath($key);
        if (!file_exists($path)) {
            return $default;
        }
        $data = unserialize(file_get_contents($path));
        if ($data['expires'] !== null && $data['expires'] < time()) {
            $this->delete($key);
            return $default;
        }
        return $data['value'];
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $path = $this->getPath($key);
        $data = [
            'value' => $value,
            'expires' => $ttl ? time() + $ttl : null,
        ];
        return file_put_contents($path, serialize($data)) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $path = $this->getPath($key);
        return file_exists($path) && unlink($path);
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    private function getPath(string $key): string
    {
        return $this->cacheDir . '/' . sha1($key) . '.cache';
    }
}

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function dispatch(EventInterface $event): void
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        // Basic implementation, a real one would be more complex
    }

    private function getListenersForEvent(EventInterface $event): iterable
    {
        $eventName = $event->getName();
        if (empty($this->listeners[$eventName])) {
            return [];
        }
        krsort($this->listeners[$eventName]);
        return call_user_func_array('array_merge', $this->listeners[$eventName]);
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
