# MaintenancePro v6.0: Developer Guide

This guide provides instructions for setting up the development environment, running tests, and contributing to the MaintenancePro project.

## 1. Prerequisites

Before you begin, ensure you have the following software installed on your system:

- **PHP 8.1 or higher:** The application is built on PHP 8.1, so you will need a compatible version.
- **Composer:** The project uses Composer for dependency management.
- **Git:** The project is managed with Git, so you will need to have it installed.

## 2. Installation

To set up the project on your local machine, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/enterprise-solutions/maintenance-pro.git
    cd maintenance-pro
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    ```
    This will install all the required PHP packages, including the development dependencies.

3.  **Set up the environment:**
    The application is designed to work out of the box with minimal configuration. The default settings are stored in `config/config.json`. You can create a `config/config.local.json` file to override the default settings for your local environment.

## 3. Running Tests

The project includes a comprehensive test suite to ensure code quality and prevent regressions. To run the tests, use the following command:

```bash
./vendor/bin/phpunit
```

This will execute all the unit, integration, and functional tests.

## 4. Contributing

We welcome contributions from the community. To contribute to the project, please follow these guidelines:

### 4.1. Coding Standards

The project follows the PSR-12 coding standard. Please ensure your code adheres to this standard before submitting a pull request. You can use a tool like PHP-CS-Fixer to automatically format your code.

### 4.2. Pull Request Process

1.  **Fork the repository:** Create a fork of the repository on your GitHub account.
2.  **Create a new branch:** Create a new branch for your feature or bug fix.
3.  **Make your changes:** Make your changes to the codebase, ensuring you follow the coding standards.
4.  **Run the tests:** Run the test suite to ensure your changes haven't introduced any regressions.
5.  **Submit a pull request:** Submit a pull request to the `main` branch of the original repository. Please provide a clear and descriptive title and description for your pull request.

## 5. Architectural Overview

For a detailed understanding of the application's architecture, please refer to the `ARCHITECTURE.md` file. This document provides a high-level overview of the system's layers, components, and design patterns.

## 6. API Reference

For a detailed reference of the public-facing API, please refer to the `API.md` file. This document provides information about the core contracts and services of the application.