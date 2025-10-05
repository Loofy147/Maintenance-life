# MaintenancePro v6.0: API Reference

This document provides a detailed reference for the public-facing API of MaintenancePro. It is intended for developers who need to interact with the application's core services and components.

## 1. Domain Contracts

The following interfaces define the core contracts for the application's services. These contracts are located in the `src/Domain/Contracts/` directory.

### 1.1. `CacheInterface`

The `CacheInterface` defines the contract for a caching service.

- **`get(string $key, $default = null)`:** Retrieves an item from the cache by key.
- **`set(string $key, $value, int $ttl = 3600): bool`:** Stores an item in the cache for a specified TTL.
- **`has(string $key): bool`:** Checks if an item exists in the cache.
- **`delete(string $key): bool`:** Removes an item from the cache.
- **`clear(): bool`:** Clears the entire cache.
- **`getStats(): array`:** Retrieves cache statistics.

### 1.2. `ConfigurationInterface`

The `ConfigurationInterface` defines the contract for a configuration management service.

- **`get(string $key, $default = null)`:** Retrieves a configuration value by key.
- **`set(string $key, $value): void`:** Sets a configuration value.
- **`has(string $key): bool`:** Checks if a configuration key exists.
- **`all(): array`:** Retrieves all configuration values.
- **`load(string $path): void`:** Loads configuration from a file.
- **`save(): void`:** Saves the configuration.
- **`validate(): bool`:** Validates the configuration against a schema.
- **`merge(array $config): void`:** Merges an array of configuration values.

### 1.3. `MetricsInterface`

The `MetricsInterface` defines the contract for a metrics service.

- **`increment(string $key, int $count = 1, array $tags = []): void`:** Increments a metric counter.
- **`gauge(string $key, float $value, array $tags = []): void`:** Records a gauge value.
- **`timing(string $key, float $value, array $tags = []): void`:** Records a timing value.
- **`getMetric(string $metric, array $filters = []): array`:** Retrieves a specific metric's value.
- **`getReport(string $period = 'day'): array`:** Generates a performance report.
- **`flush(): void`:** Flushes any buffered metrics.

## 2. Application Services

The following services are available in the `ServiceContainer` and provide the core functionality of the application.

### 2.1. `MaintenanceService`

The `MaintenanceService` is responsible for managing the application's maintenance mode.

- **`enable(string $reason = null, int $duration = 3600): void`:** Enables maintenance mode.
- **`disable(): void`:** Disables maintenance mode.
- **`getStatus(): array`:** Gets the current maintenance mode status.
- **`shouldBlock(array $context): bool`:** Determines if a request should be blocked based on the current maintenance mode settings.

### 2.2. `SecurityServiceInterface`

The `SecurityServiceInterface` provides security-related functionality, such as rate limiting.

- **`isRateLimited(string $ipAddress): bool`:** Checks if an IP address is currently being rate-limited.
- **`logThreat(string $ipAddress, string $threatType, array $context = []): void`:** Logs a security threat.

### 2.3. `EventDispatcherInterface`

The `EventDispatcherInterface` allows for a decoupled and extensible architecture.

- **`dispatch(object $event): void`:** Dispatches an event to all registered listeners.
- **`addListener(string $eventName, callable $listener): void`:** Adds an event listener for a specific event.