# PERFORMANCE REPORT

Asset Bloat & Duplication

- v4 CSS is enqueued twice in many contexts:
  - In `Plugin/includes/public/class-mt-public-assets.php` (conditionally on plugin routes), and
  - In `Plugin/includes/core/class-mt-plugin.php` (unconditionally when filter `mt_enable_css_v4` is true).
  - Additionally, legacy CSS bundles are loaded alongside v4, increasing CSS payload and causing specificity conflicts.

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

Quick Wins Checklist

- Remove duplicate v4 enqueues from `class-mt-plugin.php` and keep legacy off on v4 routes.
- Switch to minified CSS/JS in production and ensure sourcemaps are not shipped.
- Audit hotfix CSS files (e.g., `mt-jury-filter-hotfix.css`) and narrow their scope to specific routes.
- Defer animations or non-critical enhancements until after first content is visible; avoid forced reflows.
