# PR: Phase 3 – Code Cleanup and Maintainability (MT-006, MT-007)

Target: feature/phase-3-code-cleanup → develop

## Summary
Removes deprecated and redundant code and enforces naming/structure conventions to reduce tech debt and improve developer experience.

## Changes
- Remove deprecated streaming exporters in Import/Export:
  - `export_candidates_stream_deprecated`
  - `export_evaluations_stream_deprecated`
- Consolidate Elementor integration under `/includes/elementor/` (delete old `/includes/integrations/elementor` loader + base class)
- Enforce naming conventions:
  - CSS: `mt_candidate_rollback.css` → `mt-candidate-rollback.css` (and minified)
  - JS: `admin.js` → `mt-admin.js`, `coaching.js` → `mt-coaching.js`, `debug-center.js` → `mt-debug-center.js` (enqueues updated)
- Preserve WordPress template filenames with underscores (e.g., `single-mt_candidate.php`) for compatibility

## Verification
- Admin loads with `mt-admin.js` (class-mt-plugin.php + class-mt-admin.php updated)
- Coaching features load `mt-coaching.js`
- Debug Center loads `mt-debug-center.js`
- No regressions in Elementor widgets (all code comes from `/includes/elementor/`)
- Exports continue to work via the non-deprecated paths

## Notes
- No references to removed `/includes/integrations/elementor` remain
- Minified JS filenames are not directly enqueued; build pipeline unaffected
- Additional naming anomalies not found beyond template files (kept by design)

## Checklist
- [ ] E2E sanity of admin and coaching pages
- [ ] Quick regression test of exports
- [ ] Stylelint/PHPCS pass

