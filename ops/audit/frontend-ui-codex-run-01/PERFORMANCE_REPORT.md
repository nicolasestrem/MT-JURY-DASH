Mobility Trailblazers — Performance Audit (Read‑Only)

Scope: CSS/JS payload, duplication, enqueue conditions, minified assets, conditional loading.

Constraints Acknowledged
- No CSS changes to avoid regressions. Performance and responsiveness recommendations focus on JS behavior, image markup, and conditional loading.

Summary
- CSS layering: v4 + legacy layers exist; no change recommended to CSS enqueues per constraint.
- JS surface: Multiple admin/utility scripts present; ensure conditional enqueues remain tight to templates/screens.
- Minified assets: Minified JS exists under `Plugin/assets/min/js`. Consider switching enqueues to `.min.js` in production builds (no CSS changes involved).

CSS Opportunities (Deferred)
- No CSS refactors recommended. Keep current layering intact to prevent regressions.

JS Opportunities
- Minified in production: Switch to `.min.js` when `!WP_DEBUG` (or via environment). Current enqueues do not reference `assets/min/`.
- Conditional enqueue: Ensure admin-only scripts (import, candidate editor, debug center) load strictly on their screen IDs. Most checks exist, but a second pass can trim more.
- Remove unused files: `mt-jury-filters.js` appears unused per renderer comments; move to legacy or delete to prevent accidental loads.
- Responsive behavior via JS: Use `matchMedia('(max-width: 768px)')` to skip non-critical JS work on small screens (animations, frequent DOM queries), improving mobile responsiveness.
- Debounce/throttle: Ensure scroll/resize handlers are debounced via `MTEventManager` helpers to reduce layout thrashing on mobile.

Network & Render Path
- CSS: No changes proposed.
- JS execution: `frontend.js` uses intervals for rankings refresh; respects visibility and stops animations (good). Consider `requestIdleCallback` for non-critical DOM updates where supported, and gate under `matchMedia` for small screens.

Localization Cost
- Many UI strings localized via `wp_localize_script`. Ensure only the needed bundles are sent per screen to avoid shipping unused strings.

Risks
- If `mt_enable_css_v4` is false, code attempts to enqueue `assets/css/v3/*`, but the repo lacks that directory on develop. This becomes a broken-styles scenario and can degrade First Meaningful Paint.

Checklist (Suggested)
- Use `.min.js` variants in production.
- Strictly condition JS enqueues by screen/shortcode.
- Remove dead/unused JS modules.
- Apply responsive behavior gating in JS (matchMedia, debounce).
