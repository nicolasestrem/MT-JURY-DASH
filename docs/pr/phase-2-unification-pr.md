# PR: Phase 2 – Architectural Unification (Candidate Pages CPT‑free)

Target: `feature/phase-2-architectural-unification` → `develop`

## Summary

This PR removes front‑end dependency on the `mt_candidate` CPT, restores `/candidate/{slug}/` pages using a robust router and repository pattern, and reintroduces a full candidates admin interface within the plugin. It also fixes missing overview/criteria on the candidate page, improves jury dashboard images, and shows a criterion summary at the top of the evaluation page.

## Key Changes

- Add CPT‑free router: `includes/core/class-mt-candidate-router.php`
- Update template loader to use query var/global instead of CPT
- Remove candidate CPT registration and meta boxes
- Add CPT‑free candidates admin management (list + edit)
- Fix public candidate template (overview + criteria)
- Show candidate photos on jury dashboard cards with better framing
- Show criteria summary above evaluation scoring grid
- Replace CPT count in admin widget with repository count

## Files

- Added: `Plugin/includes/core/class-mt-candidate-router.php`
- Updated: `Plugin/includes/core/class-mt-template-loader.php`, `Plugin/includes/core/class-mt-post-types.php`, `Plugin/includes/core/class-mt-plugin.php`, `Plugin/mobility-trailblazers.php`, `Plugin/templates/frontend/single/single-mt_candidate-enhanced-v2.php`, `Plugin/templates/frontend/jury-dashboard.php`, `Plugin/templates/frontend/jury-evaluation-form.php`, `Plugin/templates/admin/dashboard-widget.php`

## How to Test

1. Save permalinks (Settings → Permalinks → Save) to ensure `/candidate/{slug}/` works.
2. Visit a few candidate URLs; confirm hero, overview, and criteria sections render.
3. Log in as a jury member and verify assigned candidates show photos and improved framing.
4. Open the evaluation page; confirm the five criteria summaries appear above the scoring grid.
5. Admin: Navigate to Mobility Trailblazers → Candidates; add/edit a candidate; verify front‑end updates.

## Backward Compatibility

- Front‑end works without the `mt_candidate` CPT. The router sets a fake post with `post_type = 'mt_candidate'` for CSS/body classes.
- Optional: add redirects from legacy CPT single URLs (not included).

## Security

- Admin actions secured with nonces and `mt_manage_candidates` capability; admins fallback to `manage_options` for visibility.
- Repository inputs sanitized; outputs escaped.

## Screenshots

- Candidate page hero + criteria (after)
- Jury dashboard card with photo (after)
- Evaluation page with criteria summary (after)

## Checklist

- [ ] npm test (Playwright)
- [ ] npm run i18n:validate
- [ ] npm run build (CSS/JS if needed)
- [ ] ./vendor/bin/phpcs
- [ ] npx stylelint

