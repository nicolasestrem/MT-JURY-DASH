Mobility Trailblazers — Frontend UI Audit (Read‑Only)

Scope: CSS/HTML templates, enqueue order, responsive behaviors, v4 token interactions. Based on branch develop. Source inspected under Plugin/ and includes/ (Production Source Code folder not present on develop).

Constraints Acknowledged
- No CSS refactors or styling changes due to high regression risk.
- Responsiveness improvements are in scope (via templates/markup/JS/enqueue conditions), using existing CSS utilities where possible.

Summary
- Overall: v4 CSS is present, scoped resets exist, and public templates wrap content in `.mt-root`. For responsiveness, leverage existing utilities/classes; avoid altering CSS definitions.
- Duplication: Parallel implementations exist under `Plugin/includes/public/renderers/` and `includes/public/renderers/` (e.g., `class-mt-shortcode-renderer.php`). Risk of drift and double-enqueue if both code paths activate.
- Enqueue: `includes/core/class-mt-plugin.php::enqueue_frontend_assets()` loads v4 tokens/reset/base/components/pages, plus legacy layers. We will not recommend CSS removals; focus on loading only needed JS per route and structural tweaks.
- Templates: Key templates use `.mt-root` (good scoping). Elementor override CSS via `MT_Public_Assets` is noted; no changes proposed to the CSS itself.

Asset Enqueue Notes (no CSS refactors)
- Keep current CSS stack as-is to avoid regressions.
- Ensure JS enqueues remain route-scoped (shortcode/screen checks) to reduce JS overhead on mobile.
- If `mt_enable_css_v4` is disabled, v3 CSS paths 404 on this branch; avoid toggling this flag in environments using this code.

Responsive and Layout
- Breakpoints in use: 480/576/768/992/1200/1920 across files. Treat 768px as the primary mobile boundary for UX decisions (JS/markup), without altering CSS.
- Fixed widths: Legacy containers often limit to 1200px. Do not change CSS; instead, adjust templates to avoid nested containers that create double-constrained layouts on tablets.
- Image handling: Prefer existing utilities (`.mt-img-cover|contain|pos-*`) by adding the classes in markup where needed (no CSS edits) to improve card responsiveness and cropping.
- Print: No changes proposed.

Candidate Layout Risks
- Multiple candidate profile styles exist; templates pick enhanced v2 with an additional hotfix. We will not consolidate CSS. Responsiveness can still be improved by:
  - Ensuring markup uses existing image utility classes (`.mt-img-cover` etc.).
  - Avoiding fixed-width attributes in HTML (e.g., hardcoded `width`/`height` on containers) that fight responsive CSS.

v4 Token Interactions
- No recommendations to change tokens or CSS. Dynamic tokens from settings will continue to override v4 defaults at `:root` as designed.

Localization (PHP templates)
- PHP UI strings generally use `__('…', 'mobility-trailblazers')` correctly within enqueues/localization arrays.

Notable Findings
- Missing Production Source Code folder on develop; audit performed against `Plugin/` and `includes/` which appear to be the authoritative sources.
- Duplicate renderer classes under both `Plugin/` and project root `includes/` can desynchronize behavior and asset queues.
- v3 CSS references in code but no `Plugin/assets/css/v3` directory present; if `mt_enable_css_v4` returns false, styles will 404.

Responsive-Only Recommendations (no CSS edits)
- Markup utilities: Apply existing v4 utility classes in templates to improve responsiveness without CSS changes (e.g., add `.mt-img-cover`/`.mt-img-contain` to candidate images as appropriate; ensure `.mt-root` wraps feature sections).
- Avoid nested constraints: In templates, prevent nesting multiple max-width containers; keep one structural wrapper to reduce tablet crowding at 768–992px.
- Image attributes: Add responsive attributes in markup (native `loading="lazy"`, `decoding="async"`, `srcset`/`sizes`) to improve mobile rendering and layout stability (CLS) without touching CSS.
- Shortcode scoping: Ensure only relevant scripts/templates load per shortcode to reduce main-thread work on mobile pages.
- Renderer unification: Choose a single renderer path (either `Plugin/includes/...` or root `includes/...`) to avoid duplicate asset logic; this is structural, not CSS.
