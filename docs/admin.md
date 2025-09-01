# Admin Area

This document provides an overview of the admin-side functionality of the Mobility Trailblazers plugin.

## Admin Menus

The plugin adds a main menu item to the WordPress admin sidebar called **Mobility Trailblazers**.

This menu contains the following sub-menus:

*   **Dashboard:** An overview of the plugin's activity, including evaluation and assignment statistics.
*   **Candidates:** The list of candidates (custom post type).
*   **Jury Members:** The list of jury members (custom post type).
*   **Evaluations:** A list of all evaluations submitted by jury members.
*   **Assignments:** The interface for assigning candidates to jury members.
*   **Import/Export:** Tools for importing and exporting plugin data.
*   **Settings:** The main settings page for the plugin.
*   **Coaching:** A dashboard for monitoring jury evaluation progress.
*   **Developer Tools:** A debug center with tools for developers (only available in development/staging environments).
*   **Audit Log:** A log of all significant events in the plugin.

## Custom Columns

The plugin adds the following custom columns to the **Candidates** list table:

*   **Import ID:** The ID of the candidate from the import file.
*   **Organization:** The candidate's organization.
*   **Position:** The candidate's position.
*   **Category:** The candidate's award category.
*   **Top 50:** Whether the candidate is in the Top 50.
*   **Links:** Quick links to the candidate's LinkedIn profile, website, and article.

## Import/Export

The plugin provides functionality for importing and exporting data via CSV files.

*   **Import:** Candidates and jury members can be imported from a CSV file.
*   **Export:** Candidates, evaluations, and assignments can be exported to a CSV file.

## Coaching Dashboard

The **Coaching** page provides a dashboard for administrators to monitor the progress of jury member evaluations. It displays statistics such as the number of assigned, completed, and pending evaluations for each jury member.

## Maintenance Tools

The **Developer Tools** page includes a suite of maintenance tools for managing the plugin's database and cache.

**Database Operations:**

*   Optimize and repair database tables.
*   Clean up orphaned data.
*   Run database migrations.

**Cache Operations:**

*   Clear all plugin caches and transients.
*   Regenerate cache indexes.

**Reset Operations:**

*   Reset all evaluations or assignments.
*   Perform a factory reset of the plugin.

## Candidate Editor

The plugin includes an inline editor for candidate content, which can be accessed from the **Candidates** list table. This allows for quick editing of the candidate's description and evaluation criteria without having to open the full post editor.
