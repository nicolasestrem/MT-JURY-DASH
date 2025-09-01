# AJAX API

This document provides an overview of the AJAX API in the Mobility Trailblazers plugin.

## Base Handler

The plugin includes a base AJAX handler class, `MobilityTrailblazers\Ajax\MT_Base_Ajax`, which provides common functionality for all AJAX handlers, such as nonce verification, permission checks, and standardized JSON responses.

All AJAX handlers should extend this base class.

## Endpoints

The plugin registers several AJAX actions, which are handled by different classes in the `includes/ajax/` directory. These actions are used for various frontend and backend operations, including:

*   **Evaluation:** Submitting and saving evaluations.
*   **Assignments:** Managing jury member assignments.
*   **Admin:** Various administrative tasks.
*   **Import:** Importing data from CSV files.

### Nonce

All AJAX requests must include a nonce for security. The nonce is generated using `wp_create_nonce('mt_ajax_nonce')` and should be sent as the `nonce` parameter in the AJAX request.

### Response Format

The AJAX handlers return JSON-formatted responses.

*   **Success:** `{"success":true,"data":{...}}`
*   **Error:** `{"success":false,"data":{"message":"..."}}`
