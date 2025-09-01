File Map (key frontend assets)

CSS (v4 framework)

- `Plugin/assets/css/v4/mt-tokens.css` → Design tokens (colors, spacing, radius, shadows, transitions).
- `Plugin/assets/css/v4/mt-reset.css` → Reset scoped to `.mt-root` (typography base).
- `Plugin/assets/css/v4/mt-base.css` → Base elements/components.
- `Plugin/assets/css/v4/mt-components.css` → Cards, buttons, badges, forms.
- `Plugin/assets/css/v4/mt-pages.css` → Page-level compositions.

CSS (legacy/feature bundles)

- `Plugin/assets/css/mt-variables.css`, `mt-components.css`, `frontend-new.css` → Pre-v4 stack core.
- Modules: `mt-jury-dashboard-enhanced.css`, `mt-evaluation-forms.css`, `mt-candidate-grid.css`, etc.
- Hotfixes: `mt-jury-filter-hotfix.css`, `mt-hotfixes-consolidated.css`, `candidate-image-adjustments.css`.

JS (selected)

- Admin: `assets/js/admin.js`, `i18n-admin.js`, `mt-assignments.js`, `mt-modal-force.js`, `mt-modal-debug.js`.
- Frontend: `assets/js/frontend.js`, `mt-jury-filters.js`, `evaluation-fixes.js`, `evaluation-rating-fix.js`.

Enqueue points

- `Plugin/includes/public/class-mt-public-assets.php` → Registers+enqueues v4 on plugin routes; prints dynamic tokens; optional Elementor overrides.
- `Plugin/includes/core/class-mt-plugin.php` → Enqueues v4 and legacy frontend/admin assets (potential duplication with public assets manager).

Note

- Prefer a single owner for v4 enqueues (public assets manager) and conditionally load legacy only when v4 is disabled.
