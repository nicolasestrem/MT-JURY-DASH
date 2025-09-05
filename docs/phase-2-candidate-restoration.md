# Phase 2 – Candidate Pages Restoration (CPT-free)

This document explains the changes made to restore candidate-related features without relying on a Custom Post Type (CPT). It covers routing, template loading, admin management, and UI fixes for the jury dashboard and evaluation pages.

## Overview

- Fully removed front‑end dependency on the `mt_candidate` CPT.
- Implemented a robust CPT‑free router that serves `/candidate/{slug}/` from the repository (`wp_mt_candidates`).
- Kept existing enhanced templates working by creating a fake `WP_Post` for theme/body classes.
- Restored a full CRUD admin UI for candidates under the plugin’s admin menu (no CPT screens).
- Fixed missing Overview/Evaluation Criteria on the public candidate template and evaluation pages.
- Restored candidate photos on Jury Dashboard assigned‑candidate cards with better framing.

## What Changed

### 1) Router (CPT‑free)

- Added `includes/core/class-mt-candidate-router.php`:
  - Registers rewrite rule: `^candidate/([^/]+)/?$` → `index.php?mt_candidate_slug=$matches[1]`.
  - Adds `mt_candidate_slug` to `query_vars`.
  - On `wp` action, loads the candidate from `MT_Candidate_Repository`, sets `$GLOBALS['mt_current_candidate']`, and prepares a fake `WP_Post` to satisfy themes and body classes.
  - Selects the enhanced single templates from within the plugin.
  - Performs a lazy rewrite flush when the rule is missing.

- Wired in `includes/core/class-mt-plugin.php` to initialize the router.

### 2) Template Loader

- Updated `includes/core/class-mt-template-loader.php`:
  - Triggers only when `mt_candidate_slug` or `mt_current_candidate` is present.
  - Avoids `is_singular('mt_candidate')` checks entirely.
  - Continues to prefer `single-mt_candidate-enhanced-v2.php` > `enhanced.php` > base.

### 3) Removed Candidate CPT Registration

- Updated `includes/core/class-mt-post-types.php`:
  - Removed `register_post_type('mt_candidate', ...)` and candidate meta boxes/save handlers.
  - Kept `mt_jury_member` CPT intact.

### 4) Admin Candidate Management (CPT‑free)

- `includes/admin/class-mt-candidates-admin.php` and `includes/admin/class-mt-candidates-list-table.php` provide full CRUD in mt‑admin:
  - List, search, sort, pagination, bulk actions (delete/export placeholder).
  - Create/Edit candidate with fields: name, slug, organization, position, country, LinkedIn, website, description, photo (media uploader).
  - View link to `/candidate/{slug}/`.
  - Capability: `mt_manage_candidates` (falls back to `manage_options` so admins always see it).

### 5) Public Template Fixes (Enhanced v2)

- `templates/frontend/single/single-mt_candidate-enhanced-v2.php`:
  - Robust overview extraction with fallbacks: `overview`, `ueberblick`, `überblick`, `uberblick`, `description`, `summary`.
  - Criteria cards prefer structured keys in `description_sections`:
    - `mut_pioniergeist`, `innovationsgrad`, `umsetzungskraft_wirkung`, `relevanz_mobilitaetswende`, `vorbild_sichtbarkeit`.
  - Falls back to parsing a combined `evaluation_criteria` field when structured keys are absent.
  - Uses candidate photo from repository (`photo_attachment_id`) when no featured image is available.

### 6) Jury Dashboard – Assigned Candidate Photos

- `templates/frontend/jury-dashboard.php`:
  - Cards now render candidate photo via `photo_attachment_id` or `post_id` thumbnail.
  - Added CSS to improve framing (`object-position: 50% 20%`) to reduce head cropping.

### 7) Evaluation Page – Criteria Summary Block

- `templates/frontend/jury-evaluation-form.php`:
  - Renders criterion details above the scoring grid.
  - Supports both English and German structured keys.

### 8) Dashboard Widget Count

- `templates/admin/dashboard-widget.php`:
  - Switched candidate count from `wp_count_posts('mt_candidate')` to `MT_Candidate_Repository::count()` to avoid warnings after CPT removal.

## Files Touched

- Added: `Plugin/includes/core/class-mt-candidate-router.php`
- Updated:
  - `Plugin/includes/core/class-mt-template-loader.php`
  - `Plugin/includes/core/class-mt-post-types.php`
  - `Plugin/includes/core/class-mt-plugin.php`
  - `Plugin/mobility-trailblazers.php` (removed ad‑hoc/old routers)
  - `Plugin/templates/frontend/single/single-mt_candidate-enhanced-v2.php`
  - `Plugin/templates/frontend/jury-dashboard.php`
  - `Plugin/templates/frontend/jury-evaluation-form.php`
  - `Plugin/templates/admin/dashboard-widget.php`

## How Routing Works (CPT‑free)

1. `/candidate/{slug}/` matches the rewrite rule and sets `mt_candidate_slug`.
2. Router loads the candidate from `MT_Candidate_Repository` by slug and stores it in `$GLOBALS['mt_current_candidate']`.
3. A fake `WP_Post` is created so body classes and theme/template functions behave like a normal single page.
4. Template loader selects an in‑plugin template (enhanced v2 → enhanced → base).

Advantages:
- No dependency on CPT registry.
- Works with permalink structures reliably.
- Maintains compatibility with existing templates and CSS.

## Admin Usage

- Navigate to: `Dashboard → Mobility Trailblazers → Candidates`.
- Requires capability `mt_manage_candidates` (admins also allowed via fallback).
- Use the list page to search/sort; click Add New to create; Edit to update; Delete to remove.
- Upload/select a photo via WordPress media modal.

## Testing Steps

1. Permalinks: Visit `Settings → Permalinks` and click Save once (ensures rewrite rules include `/candidate/{slug}/`).
2. Public candidate page: Visit `/candidate/{slug}/` and verify:
   - Hero renders with photo (featured image or repository photo).
   - Overview and Evaluation Criteria sections show correctly.
3. Jury dashboard: Log in as a jury member and check assigned candidate cards show photos without severe head cropping.
4. Evaluation page: Open “Evaluate” and confirm the criterion summary block is visible above the scoring grid.
5. Admin: Use the Candidates screen to add/edit a candidate and verify changes reflect on the public page.

## Backward Compatibility

- Legacy CPT single URLs can be 301‑redirected to `/candidate/{slug}/` if desired (not enabled by default).
- Most templates still receive `post_type = 'mt_candidate'` via the fake post to preserve existing CSS selectors.
- Dashboard/admin areas referencing `wp_count_posts('mt_candidate')` should be switched to repository calls; the dashboard widget is already updated.

## Known Limitations / Follow‑ups

- `mt_award_category` taxonomy remains registered to the old CPT; harmless, but can be pruned or repurposed.
- If any theme CSS relies on core CPT archive selectors, those styles won’t apply to router pages; use the plugin’s enhanced templates for consistency.
- Optional: add 301 redirects from legacy CPT singles; add import/export to candidate admin; wire Elementor image position to dashboard images.

## Security Notes

- Admin actions use nonces and capability checks (`mt_manage_candidates`).
- All repository inputs are sanitized; outputs are escaped on render.

## Release Notes

- Restored candidate pages and evaluation features without CPT.
- Reintroduced admin candidate management as a dedicated plugin page.
- Improved image handling and criteria presentation.

