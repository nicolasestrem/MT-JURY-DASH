File Map (Key Frontend/JS Assets)

CSS — v4 framework
- Plugin/assets/css/v4/mt-tokens.css
- Plugin/assets/css/v4/mt-reset.css
- Plugin/assets/css/v4/mt-base.css
- Plugin/assets/css/v4/mt-components.css
- Plugin/assets/css/v4/mt-pages.css

CSS — legacy/public
- Plugin/assets/css/mt-variables.css
- Plugin/assets/css/mt-components.css
- Plugin/assets/css/frontend-new.css (legacy main)
- Plugin/assets/css/mt-candidate-grid.css
- Plugin/assets/css/enhanced-candidate-profile.css
- Plugin/assets/css/candidate-single-hotfix.css
- Multiple candidate/profile overrides: candidate-profile-*.css, candidate-enhanced-v2*.css

JS — frontend/admin
- Plugin/assets/js/frontend.js (public; intervals, error handling)
- Plugin/assets/js/mt-event-manager.js (centralized event binding)
- Plugin/assets/js/mt-assignments.js (admin assignments)
- Plugin/assets/js/mt-evaluations-admin.js (admin evaluations)
- Plugin/assets/js/mt-settings-admin.js (admin settings)
- Plugin/assets/js/mt-jury-filters.js (not enqueued per renderer; debug logs present)
- Minified variants under Plugin/assets/min/js/* (not referenced by enqueues)

Enqueue/Localization (PHP)
- includes/core/class-mt-plugin.php (frontend assets + admin scripts; localize)
- Plugin/includes/public/class-mt-public-assets.php (v4 gating, Elementor overrides)
- Plugin/includes/public/renderers/class-mt-shortcode-renderer.php (shortcodes; v3 fallback paths)
- includes/core/class-mt-i18n-handler.php and Plugin/includes/core/class-mt-i18n-handler.php (JS i18n keys)

Templates (`.mt-root` wrapper present)
- Plugin/templates/frontend/jury-dashboard.php
- Plugin/templates/frontend/candidates-grid.php
- Plugin/templates/frontend/evaluation-stats.php
- Plugin/templates/frontend/winners-display.php
