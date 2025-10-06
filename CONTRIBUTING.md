# Contributing to MaintenancePro

First off, thank you for considering contributing to MaintenancePro! It's people like you that make open source such a great community. We welcome any and all contributions.

## How to Contribute

1.  **Fork the repository** on GitHub.
2.  **Clone your fork** to your local machine.
3.  **Create a new branch** for your feature or bugfix. Please follow the branching strategy outlined below.
4.  **Make your changes** and commit them with clear, descriptive messages.
5.  **Push your changes** to your fork.
6.  **Open a pull request** from your branch to our `develop` branch.

## Branching Strategy

We follow a structured branching strategy to ensure code quality and stability.

### 1. Main Branch (`main`)
- The single source of truth for production-ready code.
- Always stable, fully tested, and ready for deployment.
- Only merge pull requests (PRs) that have passed CI/CD pipelines and code reviews.
- Tag releases from this branch.

### 2. Development Branch (`develop`)
- Integrated branch for the latest development changes.
- All completed features and fixes are merged here first.
- Represents the next release version in progress.
- Maintained in a deployable state but may include unstable code.
- Used for internal staging and QA builds.

### 3. Feature Branches (`feature/<feature-name>`)
- Branched off from `develop`.
- Isolate work on individual features or improvements.
- Naming: `feature/login-enhancement`, `feature/cache-optimization`.
- Merged back into `develop` when feature is complete and tested.
- Short-lived; deleted after merge to keep repo clean.

### 4. Bugfix Branches (`bugfix/<issue-id-or-description>`)
- Branched from `develop`.
- Used for fixing bugs found during development or testing.
- Naming: `bugfix/fix-healthcheck-error`, `bugfix/issue-123`.
- Merged back into `develop`.

### 5. Release Branches (`release/<version>`)
- Branched from `develop` when ready to start release preparations.
- Used for final testing, bug fixes, documentation, and release notes.
- Naming: `release/v6.0.0`, `release/v6.1.0`.
- After QA signoff, merged into `main` and tagged.
- Also merged back into `develop` to keep it up to date.

### 6. Hotfix Branches (`hotfix/<version>`)
- Branched from `main` for urgent production fixes.
- Naming: `hotfix/v6.0.1`.
- After fix, merged into both `main` (tag and release) and `develop`.

## Tagging Strategy

We use Semantic Versioning (`vMAJOR.MINOR.PATCH`) for our releases.

- **MAJOR** version for incompatible API changes.
- **MINOR** version for backward-compatible functionality.
- **PATCH** version for backward-compatible bug fixes.

Tags are prefixed with `v`, e.g., `v6.0.0`. Pre-release suffixes like `-rc1` or `-beta1` may be used.

## Coding Standards

- Please follow **PSR-12** coding standards. We use `php-cs-fixer` to enforce this.
- Write clean, readable, and well-documented code.

## Running Tests

Before submitting a pull request, please ensure all tests pass:

```bash
composer install
composer test
```

## Pull Request Process

1.  Ensure your PR is targeted at the `develop` branch.
2.  Provide a clear title and description for your changes.
3.  Link to any relevant issues.
4.  Your PR must pass all CI checks (tests, code style, static analysis).
5.  At least one core contributor must approve the PR before it can be merged.

Thank you again for your contribution!