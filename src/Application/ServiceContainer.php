<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

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