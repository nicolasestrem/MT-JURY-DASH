# PERFORMANCE REPORT

Asset Bloat & Duplication

- v4 CSS is enqueued twice in many contexts:
  - In `Plugin/includes/public/class-mt-public-assets.php` (conditionally on plugin routes), and
  - In `Plugin/includes/core/class-mt-plugin.php` (unconditionally when filter `mt_enable_css_v4` is true).
  - Additionally, legacy CSS bundles are loaded alongside v4, increasing CSS payload and causing specificity conflicts.
 - Admin page enqueues overlap with template enqueues:
   - `Plugin/includes/admin/class-mt-admin.php` enqueues `mt-admin` globally on plugin pages, while `Plugin/templates/admin/assignments.php` directly enqueues `mt-assignments.js`, `mt-modal-fix.css`, and a debug script. Prefer centralized enqueues to avoid duplicates and ensure consistent cache-busting.

Opportunities

- Conditionalize by route:
  - Use `MT_Public_Assets::is_mt_public_route()` gating (already present) and let `MT_Public_Assets` be the sole source of v4 enqueues.
  - When v4 active on a route, dequeue legacy handles (use `MT_Public_Assets::get_legacy_css_handles()` for known lists).
- Minified assets:
  - Prefer `Plugin/assets/min/*` where feasible in production (`MT_Config::is_production()`), keeping readable sources for staging/dev.
- Inline tokens:
  - Instead of echoing a `<style>` in `print_dynamic_tokens()`, consider `wp_add_inline_style('mt-v4-tokens', $css)` so the variables are guaranteed to be applied with the tokens handle and cached with it.
- JS loading:
  - Most scripts load in footer; continue ensuring per-screen enqueues (e.g., only load `mt-assignments.js` on the assignments admin screen).
  - Split large admin bundles if possible; avoid binding handlers for screens that are not active.
- Remove debug-only assets in production:
   - `mt-modal-debug.js` should not load on production; wrap in `WP_DEBUG` check and guard logs.
- Remove unused/duplicate CSS:
  - Investigate `Plugin/assets/css/frontend.css` and `Plugin/assets/css/frontend/frontend.css` which appear minimal and possibly superseded by `frontend-new.css`.
   - Review `mt-modal-fix.css`: it is enqueued from the assignments template; if consolidated fixes exist (e.g., in v4/components), avoid a global modal fix file.

Quick Wins Checklist

- Remove duplicate v4 enqueues from `class-mt-plugin.php` and keep legacy off on v4 routes.
- Switch to minified CSS/JS in production and ensure sourcemaps are not shipped.
- Audit hotfix CSS files (e.g., `mt-jury-filter-hotfix.css`) and narrow their scope to specific routes.
- Defer animations or non-critical enhancements until after first content is visible; avoid forced reflows.
 - Consolidate candidate grid and responsive styles to reduce duplication across `frontend/_responsive.css` and `mt-candidate-grid.css`.
