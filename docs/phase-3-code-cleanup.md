# Phase 3 – Code Cleanup and Maintainability

This document summarizes the Phase 3 work to remove redundant code, enforce conventions, improve build portability, and simplify the Evaluations admin UI.

## Highlights
- Removed deprecated exporters (streaming methods) from Import/Export
- Consolidated Elementor integration under `includes/elementor/`
- Enforced naming conventions (JS: `mt-*`, CSS: hyphenated)
- Added cross-platform build script for Windows (`scripts/build-assets.js`)
- Removed development-only debug UI on Assignments page
- Simplified Evaluations admin (no Approve/Reject/Reset; no Status filter/column)

## Removed Code
- `export_candidates_stream_deprecated` and `export_evaluations_stream_deprecated`
- `includes/integrations/elementor/` (old loader and base; not used)
- Assignments page Debug Section (Development Only)

## Naming & Structure Conventions
- JS files renamed to `mt-*` (mt-admin.js, mt-coaching.js, mt-debug-center.js)
- CSS `mt_candidate_rollback.css` → `mt-candidate-rollback.css`
- WordPress template filenames with underscores are preserved for compatibility

## Evaluations Admin Simplification
- Removed bulk Approve/Reject and Reset actions
- Removed Status filter and Status column from the table
- Admin evaluates: view details / delete only

## Build Portability (Windows)
- New Node-based build: `node scripts/build-assets.js`
  - Minifies `Plugin/assets/js` and `Plugin/assets/css` (skips already-minified and /min/ folders)
- NPM scripts updated:
  - `npm run build` / `npm run build:win`
- Resolved minified naming mismatches (ensure `mt-*.min.js` exist for production enqueues)

## CSS Fixes
- frontend.css: fixed attribute selectors and stray brace; removed escaped `!important`
- Jury dashboard cards: improved image framing (object-position)

## Migration Note
- Hidden `mt_candidate` CPT remains registered (no UI/public) to allow migration tools and legacy APIs to work safely. Frontend remains CPT-free.
