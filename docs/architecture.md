# Plugin Architecture

This document provides a high-level overview of the Mobility Trailblazers plugin's architecture.

## Directory Structure

The plugin's source code is organized within the `Production Source Code` directory. Key subdirectories include:

*   `includes/`: Contains the core PHP source code for the plugin.
    *   `admin/`: Admin-facing functionality.
    *   `ajax/`: AJAX handlers.
    *   `cli/`: WP-CLI command implementations.
    *   `core/`: Core plugin functionality, including the main plugin class, autoloader, and dependency injection container.
    *   `interfaces/`: PHP interfaces.
    *   `providers/`: Service providers for the dependency injection container.
    *   `repositories/`: Data access layer, responsible for database interactions.
    *   `services/`: Business logic and services.
*   `assets/`: Contains CSS, JavaScript, and image assets.
*   `languages/`: Translation files.
*   `templates/`: Template files for rendering frontend and admin views.

## Dependency Injection

The plugin utilizes a lightweight dependency injection (DI) container (`MobilityTrailblazers\Core\MT_Container`) to manage class dependencies. This promotes loose coupling and makes the code more modular and testable.

Services and repositories are registered with the container through service providers.

## Service Providers

Service providers are used to bootstrap services and their dependencies. They are registered with the DI container and are responsible for binding interfaces to concrete implementations.

The main service providers are:

*   `MobilityTrailblazers\Providers\MT_Repository_Provider`: Registers the plugin's repositories.

## Candidate Routing (CPT‑free)

- Router: `MobilityTrailblazers\Core\MT_Candidate_Router`
  - Adds rewrite rule for `/candidate/{slug}/` and query var `mt_candidate_slug`.
  - Loads candidate via repository and prepares a fake `WP_Post` to satisfy templates/themes.
  - Template selection is handled by `MT_Template_Loader`, which prefers in‑plugin enhanced templates.
  - CPT `mt_candidate` is not required for front‑end pages.
*   `MobilityTrailblazers\Providers\MT_Services_Provider`: Registers the plugin's services.

This pattern allows for a centralized and organized way to manage the plugin's dependencies.
