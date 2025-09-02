Mobility Trailblazers — Performance Audit (Read‑Only)

Scope: CSS/JS payload, duplication, enqueue conditions, minified assets, conditional loading.

Summary
- CSS layering: v4 framework + legacy component system both load on public routes. This increases bytes, specificity conflicts, and style recalculations.
- JS surface: Multiple admin/utility scripts present; not all are conditionally enqueued. Some legacy or unused modules remain in repo but not loaded.
- Minified assets: Minified JS exists under `Plugin/assets/min/js`, but PHP enqueues reference unminified paths; conditional min usage not detected.

CSS Opportunities
- De-duplicate frameworks: When `mt_enable_css_v4` is true, avoid enqueuing `mt-variables.css` and `mt-components.css`. Keep only v4 + page-specific CSS.
- Remove hotfix tail: `candidate-single-hotfix.css` is still enqueued alongside `enhanced-candidate-profile.css`. Consolidate and remove the hotfix include to drop extra CSS.
- Print styles: Spread across several files. Consolidate print rules into a single layer to minimize overrides during print rendering.
- Elementor neutralization: Inline `all: unset` overrides are powerful. Ensure they scope strictly under `.mt-root` and load after Elementor to minimize cascade churn.

JS Opportunities
- Minified in production: Switch to `.min.js` when `!WP_DEBUG` (or via environment). Current enqueues do not reference `assets/min/`.
- Conditional enqueue: Ensure admin-only scripts (import, candidate editor, debug center) load strictly on their screen IDs. Most checks exist, but a second pass can trim more.
- Remove unused files: `mt-jury-filters.js` appears unused per renderer comments; move to legacy or delete to prevent accidental loads.

Network & Render Path
- CSS order: Current order loads five v4 sheets plus multiple legacy sheets. Consider concatenating v4 layers (tokens+reset+base+components+pages) for production, or at least leverage HTTP/2 caching with immutable versions.
- JS execution: `frontend.js` uses intervals for rankings refresh; respects visibility changes and stops animations, which helps. Consider requestIdleCallback guards for non-critical DOM work.

Localization Cost
- Many UI strings localized via `wp_localize_script`. Ensure only the needed bundles are sent per screen to avoid shipping unused strings.

Risks
- If `mt_enable_css_v4` is false, code attempts to enqueue `assets/css/v3/*`, but the repo lacks that directory on develop. This becomes a broken-styles scenario and can degrade First Meaningful Paint.

Checklist (Suggested)
- Use `.min.js` and `.min.css` variants in production.
- Gate legacy CSS when v4 is active.
- Remove dead/unused CSS and JS.
- Strictly condition enqueues by screen/shortcode.
- Scope Elementor neutralization carefully.
