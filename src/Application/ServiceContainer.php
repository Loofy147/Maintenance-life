<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

/**
 * A simple dependency injection container.
 *
 * This container allows for registering services as factories, singletons, or
 * instances, and resolves them on demand. It also supports array-like access.
 */
class ServiceContainer implements \ArrayAccess
{
    private array $factories = [];
    private array $instances = [];

    /**
     * Registers a service factory that creates a new instance on each call.
     *
     * @param string   $name    The abstract name of the service.
     * @param callable $factory The factory that creates the service instance.
     */
    public function register(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    /**
     * Registers a service as a singleton, ensuring it is instantiated only once.
     *
     * @param string   $name    The abstract name of the service.
     * @param callable $factory The factory that creates the singleton instance.
     */
    public function singleton(string $name, callable $factory): void
    {
        $this->factories[$name] = function () use ($factory) {
            static $instance;
            if ($instance === null) {
                $instance = $factory($this);
            }
            return $instance;
        };
    }

    /**
     * Registers an existing object instance in the container.
     *
     * @param string $name     The abstract name of the service.
     * @param mixed  $instance The object instance.
     */
    public function instance(string $name, $instance): void
    {
        $this->instances[$name] = $instance;
    }

    /**
     * Retrieves a service from the container.
     *
     * @param string $name The abstract name of the service.
     * @return mixed The service instance.
     * @throws \RuntimeException If the service is not found.
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
     * Checks if a service is registered in the container.
     *
     * @param string $name The abstract name of the service.
     * @return bool True if the service exists, false otherwise.
     */
    public function has(string $name): bool
    {
        return isset($this->factories[$name]) || isset($this->instances[$name]);
    }

    /**
     * Checks if a service exists using array access.
     *
     * @param mixed $offset The service name.
     * @return bool True if the service exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Gets a service using array access.
     *
     * @param mixed $offset The service name.
     * @return mixed The service instance.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Sets a service using array access.
     *
     * If the value is callable, it's registered as a factory. Otherwise, it's
     * registered as an instance.
     *
     * @param mixed $offset The service name.
     * @param mixed $value  The service instance or factory.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_callable($value)) {
            $this->register($offset, $value);
        } else {
            $this->instance($offset, $value);
        }
    }

    /**
     * Unsets a service using array access.
     *
     * @param mixed $offset The service name.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->factories[$offset], $this->instances[$offset]);
    }
}