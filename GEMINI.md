# Mobility Trailblazers WordPress Plugin

## Project Overview

This is a comprehensive WordPress plugin for managing the "25 Mobility Trailblazers in 25" award platform. It's designed to handle the entire award selection process, from candidate nominations to jury evaluations and public announcements.

The project is built with modern PHP practices, including a dependency injection container, a service-provider architecture, and a repository pattern for data access. It also includes a robust testing suite using Playwright for end-to-end testing.

**Key Technologies:**

*   **Backend:** PHP, WordPress
*   **Frontend:** JavaScript, CSS
*   **Testing:** Playwright, PHPUnit
*   **Build Tools:** Node.js, npm

## Building and Running

### Prerequisites

*   PHP 7.4+
*   WordPress 5.8+
*   MySQL 5.7+ / MariaDB 10.3+
*   Node.js 16+

### Installation

1.  Upload the `Plugin` directory to `/wp-content/plugins/mobility-trailblazers/`.
2.  Activate the plugin through the WordPress Admin -> Plugins.
3.  Run the setup wizard at MT Award System -> Setup.

### Building Assets

The project uses `npm` to manage frontend dependencies and build scripts.

*   **Install dependencies:**
    ```bash
    npm install
    ```
*   **Build all assets (CSS and JS):**
    ```bash
    npm run build
    ```
*   **Minify CSS:**
    ```bash
    npm run build:css
    ```
*   **Minify JS:**
    ```bash
    npm run build:js
    ```

### Running Tests

The project uses Playwright for end-to-end testing and PHPUnit for unit testing.

*   **Run all Playwright tests:**
    ```bash
    npm test
    ```
*   **Run PHPUnit tests:**
    ```bash
    ./vendor/bin/phpunit
    ```

## Development Conventions

*   **Coding Standards:** The project follows the WordPress coding standards. Use `phpcs` to check for compliance:
    ```bash
    ./vendor/bin/phpcs --standard=WordPress .
    ```
*   **Repository-Service Pattern:** All data access should be handled by repositories, and business logic should be implemented in services.
*   **Dependency Injection:** The project uses a dependency injection container to manage object creation and dependencies.
*   **AJAX Security:** All AJAX handlers must verify nonces to prevent CSRF attacks.
*   **Documentation:** All new features and changes should be documented in the `docs` directory.
