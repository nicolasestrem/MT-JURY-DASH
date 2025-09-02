Mobility Trailblazers — Frontend UI Audit (Read‑Only)

Scope: CSS/HTML templates, enqueue order, responsive behaviors, v4 token interactions. Based on branch develop. Source inspected under Plugin/ and includes/ (Production Source Code folder not present on develop).

Summary
- Overall: v4 CSS is present, scoped resets exist, and public templates wrap content in `.mt-root`. Multiple legacy CSS layers remain enqueued alongside v4, increasing override risk and payload size.
- Duplication: Parallel implementations exist under `Plugin/includes/public/renderers/` and `includes/public/renderers/` (e.g., `class-mt-shortcode-renderer.php`). Risk of drift and double-enqueue if both code paths activate.
- Enqueue: `includes/core/class-mt-plugin.php::enqueue_frontend_assets()` loads v4 tokens/reset/base/components/pages, plus `mt-variables.css`, `mt-components.css`, and feature CSS. Potentially overlapping systems.
- Templates: Key templates use `.mt-root` (good scoping). Elementor override inline CSS added via `MT_Public_Assets` to reduce bleed.

Asset Enqueue Order and Conflicts
- v4 sequence: `mt-v4-tokens` → `mt-v4-reset` → `mt-v4-base` → `mt-v4-components` → `mt-v4-pages` (good dependency chain).
- Legacy layer: Immediately after v4, code enqueues `mt-variables.css` and `mt-components.css` plus page/feature CSS. Different token namespaces (`--mt-color-*` vs `--mt-*`) reduce direct clashes, but base component class names overlap (e.g., `.mt-` family). High specificity or `!important` in legacy files can override v4 unintentionally.
- Renderer split: Shortcode renderer(s) conditionally enqueue legacy v3 assets if `mt_enable_css_v4` is false, but the repo lacks `Plugin/assets/css/v3` files. If v4 is disabled, enqueues will 404.
- Elementor overrides: `MT_Public_Assets::maybe_optimize_third_party_css()` injects `all: unset` inside `.mt-root` for Elementor containers. This is powerful; ensure overrides only run where `.mt-root` definitely wraps widget trees to avoid nuking intended styles.

Responsive and Layout
- Breakpoints: v4 uses 768px as primary mobile cutoff, with additional component queries. Legacy CSS uses a mixture of 480/768/992/1200/1920. Mixed systems may cause inconsistent tablet behaviors.
- Fixed max-widths: Frequent `max-width: 1200px` containers across legacy CSS (`mt-jury-dashboard-*.css`, candidate CSS) can conflict with v4 container sizing or cause overflow in nested grids.
- Image handling: v4 provides `.mt-img-cover|contain|pos-*` utilities and card wrappers. Legacy candidate CSS redefines image sizes and min/max widths; combined loading risks duplicated behaviors and specificity fights.
- Print styles: Present in several files (rankings rich editor, v4 pages). No central consolidation; risk of conflicting print outputs.

Candidate Layout Risks
- Multiple candidate profile styles exist: `enhanced-candidate-profile.css`, `candidate-profile-*`, `candidate-enhanced-v2*.css`, and hotfix files. Templates choose enhanced v2; additional hotfix (`candidate-single-hotfix.css`) is still enqueued, indicating pending consolidation.
- Spacing and radius tokens differ between v4 (`--mt-space*`, `--mt-radius*`) and legacy (`--mt-spacing-*`, `--mt-radius-*`). If both layers are used, components with generic `.mt-*` classes can end up visually inconsistent between pages.

v4 Token Interactions
- v4 tokens: `--mt-color-*`, `--mt-font-*`, `--mt-space*`. Legacy variables: `--mt-primary`, `--mt-secondary`, `--mt-spacing-*`. Names do not collide directly, but both define global `:root` scales. Dynamic tokens via `MT_Public_Assets::print_dynamic_tokens()` also write `--mt-color-*` at `:root`, intentionally overriding v4 defaults (expected).
- Recommendation: Prefer a single token source per route. Where v4 is active, avoid loading legacy `mt-variables.css` to prevent drift and simplify theming.

Localization (PHP templates)
- PHP UI strings generally use `__('…', 'mobility-trailblazers')` correctly within enqueues/localization arrays.

Notable Findings
- Missing Production Source Code folder on develop; audit performed against `Plugin/` and `includes/` which appear to be the authoritative sources.
- Duplicate renderer classes under both `Plugin/` and project root `includes/` can desynchronize behavior and asset queues.
- v3 CSS references in code but no `Plugin/assets/css/v3` directory present; if `mt_enable_css_v4` returns false, styles will 404.

Recommendations (Read‑Only)
- Consolidate to v4-only on public routes: Gate and dequeue legacy `mt-variables.css` and `mt-components.css` when v4 is active; rely on v4 components instead.
- Remove or archive legacy CSS/JS no longer enqueued (e.g., `frontend.css`, older candidate overrides) to reduce cognitive load and payload risk.
- Unify renderer location: Keep one canonical `class-mt-shortcode-renderer.php` to prevent divergent enqueues.
- Confirm `.mt-root` wraps all shortcode templates to keep reset scoped; maintain Elementor neutralization only within that root.
- Tablet audit: Normalize breakpoints to v4 (768/1200) and convert fixed `max-width: 1200px` patterns into container variables to avoid nested overflow on tablets.
