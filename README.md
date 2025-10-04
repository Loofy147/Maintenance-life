# MaintenancePro v6.0: Ultimate Enterprise Edition

MaintenancePro is a professional-grade, enterprise-ready maintenance mode system for PHP applications. It is built on modern architectural principles, including SOLID, Domain-Driven Design (DDD), and a clean, layered architecture. This system is designed to be robust, scalable, and easy to maintain, providing a seamless experience for both developers and end-users.

## 🏆 Key Features

*   **Advanced Health Check System:** A comprehensive, extensible health check system that monitors the status of the database, cache, and disk space, ensuring the application is always running optimally.
*   **Web-Based Admin Dashboard:** A secure and user-friendly admin dashboard that provides a single pane of glass for managing the maintenance mode system, viewing performance metrics, and monitoring system health.
*   **Production-Grade Circuit Breaker:** A fault-tolerant circuit breaker that prevents cascading failures by isolating unreliable external services, ensuring the application remains stable and responsive.
*   **Buffered, High-Performance Metrics:** A sophisticated, buffered metrics service that tracks key performance indicators (KPIs) with minimal performance impact, providing detailed analytics on request times, cache performance, and error rates.
*   **Schema-Validated Configuration:** A robust configuration manager that uses schema validation to prevent runtime errors caused by misconfiguration, making the system more reliable and easier to manage.
*   **Two-Layer Adaptive Cache:** A high-performance, two-layer cache system with an in-memory cache for single-request speed, backed by a persistent file cache for data integrity.
*   **Intelligent Maintenance Strategy:** An adaptive strategy that can be configured to automatically enable maintenance mode based on real-time traffic patterns, applying cost-aware and predictive principles.
*   **PSR-3 Compatible Logging:** Integrated with Monolog for powerful and flexible logging to multiple channels.
*   **Full-Featured CLI:** A comprehensive command-line interface for managing all aspects of the system.

## 🚀 Usage

### Web Interface (Admin Dashboard)

The admin dashboard provides a user-friendly interface for managing the system. To access it, navigate to `/admin` in your browser. From here, you can:
*   Enable and disable maintenance mode.
*   Manage the IP whitelist.
*   View the real-time system health report.
*   Monitor performance with the metrics dashboard.
*   View the current circuit breaker status.

### Command-Line Interface (CLI)

The CLI provides a powerful set of commands for managing the system from the command line.

| Command | Description |
| :--- | :--- |
| `enable [reason] [duration]` | Enable maintenance mode. |
| `disable` | Disable maintenance mode. |
| `status` | Check the current maintenance status. |
| `whitelist:add <ip>` | Add an IP address to the whitelist. |
| `whitelist:remove <ip>` | Remove an IP address from the whitelist. |
| `metrics:report` | Generate a performance metrics report. |
| `health:check` | Run a system health check. |
| `mock:service-call` | Call the mock external service to test the circuit breaker. |
| `mock:service-fail` | Set the mock service to a failing state. |
| `mock:service-succeed` | Set the mock service to a succeeding state. |

## ⚙️ Configuration

The application's configuration is located in `config/config.json`. The configuration is validated against a schema to prevent errors. Key settings include:

*   `maintenance.strategy`: Set to `"intelligent"` to enable the adaptive maintenance strategy.
*   `maintenance.intelligent.traffic_threshold`: The traffic threshold for the intelligent strategy.
*   `security.rate_limiting.enabled`: Enable or disable rate limiting.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a pull request with any improvements or new features.