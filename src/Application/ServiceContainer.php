<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

class ServiceContainer implements \ArrayAccess
{
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
        $this->factories[$name] = function () use ($factory) {
            static $instance;
            if ($instance === null) {
                $instance = $factory($this);
            }
            return $instance;
        };
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

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_callable($value)) {
            $this->register($offset, $value);
        } else {
            $this->instance($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->factories[$offset], $this->instances[$offset]);
    }
}