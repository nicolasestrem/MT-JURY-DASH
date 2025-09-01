# FRONTEND REPORT

Summary

- The Plugin loads both the new v4 CSS framework and legacy CSS concurrently on many pages. This creates cascade and specificity conflicts, especially around typography, spacing, and component resets.
- Frontend enqueues are duplicated: v4 is conditionally enqueued via `Plugin/includes/public/class-mt-public-assets.php` AND also enqueued in `Plugin/includes/core/class-mt-plugin.php` alongside legacy bundles.
- Several legacy CSS files rely heavily on `!important`, increasing maintenance cost and risk when combined with v4 tokens.
- Admin/Frontend JS shows some overlapping event bindings (notably assignments modal actions), risking double handlers and duplicate AJAX actions.
- Responsive CSS exists and is generally sound, but breakpoints are duplicated across files; opportunities to consolidate and rely on v4 tokens and utility classes.

Top Risks (ranked)

1) Duplicate v4 + legacy CSS enqueues cause conflicts and bloat.
   - Impact: Unpredictable styling on jury dashboard, candidate grid, and evaluation pages; harder to stabilize layouts across themes.
   - Evidence: `Plugin/includes/core/class-mt-plugin.php` enqueues v4 (tokens/reset/base/components/pages) and legacy (`mt-variables`, `mt-components`, `frontend-new.css`, modules); meanwhile `Plugin/includes/public/class-mt-public-assets.php` also registers/enqueues v4 on plugin routes.
2) Overlapping JS handlers for assignments/admin screens.
   - Impact: Double submissions/modals opening twice; hard-to-reproduce admin bugs.
   - Evidence: `Plugin/assets/js/admin.js` binds to `#mt-auto-assign-btn`, `#mt-manual-assign-btn`, `.mt-remove-assignment` while `Plugin/assets/js/mt-assignments.js` binds the same selectors.
3) Excessive `!important` in legacy CSS and rollback styles.
   - Impact: Forces high specificity and makes v4 overrides brittle; increases future refactor cost.
   - Evidence: `Plugin/assets/css/mt_candidate_rollback.css` uses `!important` across layout/spacing/typography (dozens of lines).
4) Elementor override risk via `all: unset`.
   - Impact: May unintentionally strip essential styles in Elementor widgets inside `.mt-root` container if markup is reused.
   - Evidence: Inline override added by `MT_Public_Assets::maybe_optimize_third_party_css()`.
5) Console logs shipped in production contexts.
   - Impact: Noise in browser console; minor perf impact; leaks debugging info.
   - Evidence: `Plugin/assets/js/mt-jury-filters.js` and `mt-modal-debug.js` log multiple messages.

Findings by Category

CSS (tokens, conflicts, and `!important`)

- v4 tokens present: `Plugin/assets/css/v4/mt-tokens.css` defines `--mt-*` variables used across v4 reset/base/components/pages.
- Legacy token/style bundles also present: `mt-variables.css`, `mt-components.css`, `frontend-new.css`, plus numerous module CSS (jury dashboard, candidate grid, evaluation forms, etc.).
- Conflict vector: With both v4 and legacy CSS loaded, typography (font-size/line-height), spacing (`--mt-space-*` vs legacy spacing), and component resets can conflict.
- `!important` overuse: `mt_candidate_rollback.css` (and some table/rankings CSS) rely on `!important` for layout, shadow, and sizing. This undermines v4’s layered cascade.
- Hotfix layering: `mt-jury-filter-hotfix.css` is enqueued on all plugin routes when v4 is active — verify scope to just affected pages/components to reduce global overrides.

JavaScript (events, AJAX, console)

- Double-binding risk: Both `admin.js` and `mt-assignments.js` bind the same selectors (auto/manual assign, clear all, remove assignment). One uses delegated `$(document).on(...)`, the other uses direct `.off().on(...)` — together they can still result in two handlers firing.
- Console logs: `mt-modal-debug.js` and `mt-jury-filters.js` log extensively. `mt-modal-debug.js` is referenced from an admin template; ensure it is only included behind an explicit dev/debug flag.
- AJAX hygiene: Most calls include `nonce` and use `mt_admin`/`mt_ajax` objects. Error handlers exist but are inconsistent in messaging and status handling across files.
 - Inline fallback in assignments template: `Plugin/templates/admin/assignments.php` injects inline JS with console logs and English alerts, plus enqueues `mt-modal-debug.js`. This bypasses centralized admin handlers and localization.

Responsive

- Breakpoints used: 1400, 1200, 992, 900, 768, 480. They’re repeated across multiple CSS files under `Plugin/assets/css/frontend/`.
- Grid responsiveness for candidates is solid; however, fixed pixel sizes for images and min-heights can create jumpiness. Consider `aspect-ratio`, `object-fit`, and `clamp()` for typography.
 - Candidate grid relies on many `!important` rules in `mt-candidate-grid.css` to fight theme styles; prefer scoping inside a root container (e.g., `.mt-root`) and reduce `!important` where possible.

Assets (enqueue order, duplication)

- Public v4 CSS is registered/enqueued in `MT_Public_Assets` and again in `MT_Plugin::enqueue_frontend_assets()`.
- Legacy CSS is enqueued even when v4 is enabled. This defeats the purpose of v4 isolation and introduces specificity battles.
- Suggestion: When v4 is enabled on a route, do not enqueue legacy bundles; and consider dequeuing known legacy handles returned by `MT_Public_Assets::get_legacy_css_handles()`.

Localization

- Templates: Most frontend/admin templates correctly wrap UI strings with the `mobility-trailblazers` text domain (e.g., winners display and single templates). Good coverage.
- JavaScript: Some hardcoded fallback strings exist (e.g., `Plugin/assets/js/frontend.js` uses English fallbacks like “An error occurred. Please try again.” when `mt_ajax.i18n` keys are missing). While acceptable as a safeguard, these are not translatable. Prefer always sourcing strings from localized objects and ensure keys are provided server-side.
- Admin debug content: `Plugin/templates/admin/assignments.php` includes debug UI texts gated by `WP_DEBUG`. Keep gated to non-production; ensure any visible strings in production remain localized.

File-by-File Appendix (path:line → issue → impact → suggestion)

- Plugin/includes/core/class-mt-plugin.php:386–540 (approx) → Enqueues v4 CSS unconditionally; subsequently enqueues legacy CSS bundles → v4/legacy conflicts and bloat → Gate legacy enqueues when `apply_filters('mt_enable_css_v4', true)` and plugin route is active; or fully delegate v4 to `MT_Public_Assets` only.
- Plugin/includes/core/class-mt-plugin.php:~360–520; 520–720 → v4 framework enqueued (tokens/reset/base/components/pages) then legacy stacks like `mt-frontend`, `mt-evaluation-forms`, `mt-jury-dashboard-enhanced`, plus hotfixes → Payload + specificity conflicts with MT_Public_Assets v4 → Centralize v4 in MT_Public_Assets and conditionally load modules based on route.
- Plugin/includes/public/class-mt-public-assets.php:200–220 → Enqueues v4 (tokens/reset/base/components/pages) on plugin routes → Can duplicate with core enqueues → Centralize v4 loading here; remove v4 enqueues from `class-mt-plugin.php`.
- Plugin/includes/public/class-mt-public-assets.php:312–335 → Elementor neutralization uses `all: unset` on `.elementor-widget-container` within `.mt-root` → Risk of over-resetting embedded widgets → Narrow selectors to plugin-owned containers/components; prefer targeted property resets.
- Plugin/assets/css/mt_candidate_rollback.css:[25–100, 110–124, 300–338, etc.] → Heavy `!important` usage → Specificity inflation; hard to maintain; conflicts with v4 → Replace with scoped selectors inside `.mt-root`/component blocks; leverage v4 variables and remove `!important` where possible.
- Plugin/assets/css/table-rankings-enhanced.css:323,331–332 → `display: none !important;` and hard background overrides → Accessibility and cascade risks → Use state classes on container; avoid `!important`.
- Plugin/assets/js/admin.js:237,242,257,262 → Delegated click handlers for assignments actions → Overlaps with `mt-assignments.js` direct handlers → Namespace events (e.g., `.on('click.mt-assign', ...)`) and ensure only one script binds for the page.
- Plugin/assets/js/mt-assignments.js:18–64, 49, 54, 59, 64 → Direct handlers on same selectors → Potential double-fire with admin.js → Feature-flag per page or ensure admin.js detects and skips if `#mt-auto-assign-btn` is bound; or keep all logic in `mt-assignments.js` and remove overlaps from admin bundle on that screen.
- Plugin/assets/js/mt-modal-debug.js:5,38,45,79–88,96,130,147–148 → Console logs → Debug noise in admin → Guard behind `if (window.MT_DEBUG) { ... }` or strip in production.
- Plugin/assets/js/mt-jury-filters.js:10–113 → Console logs around filter actions → Production noise → Remove or guard with a debug flag.
 - Plugin/templates/admin/assignments.php:452–560,560–840 → Enqueues `mt-assignments.js`, then `mt-modal-debug.js`, adds inline fallback handlers and English alerts → Double-bind risk, non-localized UX, console noise → Only enqueue `mt-assignments.js` on this page, drop debug script in production, and move fallback logic into the main module with i18n.
 - Plugin/assets/css/mt-candidate-grid.css:1–200,200–500 → Extensive use of `!important` across layout/spacing/hover and elementor overrides → Cascade brittleness and heavy specificity → Scope under a plugin root, reduce `!important`, replace inline style attribute matchers with component classes.
 - Plugin/assets/js/frontend.js:~680–980 → Uses localized messages but includes English fallbacks in code paths → Non-localized UX fallback → Ensure all keys are provided server-side and drop hardcoded fallbacks where feasible.
 - Plugin/templates/admin/assignments.php:~450–465 → Enqueues `mt-assignments.js` and `mt-modal-debug.js` from template → Risk of debug script inclusion outside strict debug contexts → Wrap debug script enqueue in `WP_DEBUG` and allow `MT_DEBUG` flag to control console output.

Suggested Remediations (prioritized)

1) Single source of truth for v4 CSS: Load v4 exclusively via `MT_Public_Assets` on plugin routes. In `class-mt-plugin.php`, skip v4 and legacy enqueues when v4 is enabled for that route. Optionally, deque legacy handles via `get_legacy_css_handles()`.
2) Reduce/contain `!important`: Create component-scoped rules in v4, migrate hotfixes into properly layered selectors, and remove `!important` where v4 tokens/components provide needed specificity.
3) JS handler consolidation: Move assignments interactions into `mt-assignments.js` only; namespace events; in admin.js, gate bindings by checking page-specific markers.
4) Elementor override hardening: Replace `all: unset` with minimal, targeted resets; apply only within plugin-rendered blocks to avoid collateral overrides.
5) Performance: Condition more modules by route (only enqueue candidate/evaluation/jury styles where needed), and prefer minified assets in production; consider deferring non-critical JS with `in_footer` true (already used in many) and splitting per-screen bundles.
