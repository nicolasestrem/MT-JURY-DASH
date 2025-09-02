Mobility Trailblazers — JavaScript Audit (Read‑Only)

Scope: Admin + frontend JS under `Plugin/assets/js` and related localization/enqueues in PHP.

Summary
- Error handling: Frontend central `MTErrorHandler` present with `handleAjaxError` helpers; many calls use i18n fallbacks via `getI18nText`. Good baseline.
- Event lifecycle: `mt-event-manager.js` implements namespaced bindings and cleanup, reducing leak risk. Many modules still bind directly with `on()`; off/on patterns applied in some admin scripts.
- AJAX usage: jQuery AJAX patterns used; URLs/nonce passed via `wp_localize_script` into `mt_ajax`/module-specific objects.
- Debug code: One module with explicit console logging remains in repo but appears not enqueued.

Findings
- Console logs: `Plugin/assets/js/mt-jury-filters.js` contains multiple `console.log` statements and hardcoded German strings. Shortcode renderer comments indicate it is not enqueued (“using inline JavaScript”), so logs won’t surface at runtime; still advisable to remove or gate by `WP_DEBUG`.
- Hardcoded UI strings: Several admin/frontend modules include English/German fallback strings inline (e.g., `mt-assignments.js`, `frontend.js`). Many are wrapped by module-localized `i18n` objects but not universal. Risk: partial localization coverage, inconsistent language.
- Double bindings: Some files use `$('#selector').on(...)` without `.off()`, which can double-bind if reinitialized (e.g., `mt-jury-filters.js`). Most admin flows do use `.off().on()` or centralized event manager. Low–medium risk depending on view reloads.
- Global state cleanup: `frontend.js` tracks intervals/timeouts/listeners and cleans on `beforeunload` and `visibilitychange`. Good practice. Some direct jQuery animations remain; they are stopped in cleanup.
- Error surfaces: Default fallbacks (e.g., “An error occurred”) appear often; ensure i18n keys exist in `class-mt-i18n-handler.php` for parity.

AJAX Patterns
- URL/Nonce: `mt_ajax.ajax_url` and `mt_ajax.nonce` set via `wp_localize_script`. Some modules defensively fallback to `ajaxurl` and hidden nonce inputs.
- Error handling: `error:` handlers often call `showNotification` with localized fallback; `frontend.js`’s `handleAjaxError` improves consistency on public routes.
- Timeouts: No explicit AJAX timeouts found in most modules; consider standardizing (e.g., 15s) in critical admin flows.

Enqueue/Localization
- Scripts localized via `wp_localize_script` in: `includes/core/class-mt-plugin.php` and `Plugin/includes/core/class-mt-i18n-handler.php`. Keys use text domain `mobility-trailblazers` in PHP.
- Duplication: Both `Plugin/includes/...` and root `includes/...` define localizations and enqueues for similar handles. Risk of divergence and duplicate localize calls if both paths run.

Recommendations (Read‑Only)
- Remove or gate debug logs: Delete `console.log` from `mt-jury-filters.js` or wrap with `if (window.MT_DEBUG) { … }`. Keep file if not enqueued but mark deprecated to avoid future use.
- Centralize event binding: Prefer `MTEventManager.on()` across modules; replace direct `on()` where views can re-render.
- Standardize AJAX error/timeout: Use a shared wrapper with default timeout and message surfaces; align keys with `i18n-handler`.
- Audit localization coverage: Ensure all UI strings in JS reference `mt_frontend_i18n`/module i18n, avoiding hardcoded fallbacks. Keep fallbacks as a last resort only.
- Reduce surface area: Remove unreferenced JS modules or move to a `legacy/` folder to avoid accidental enqueues.
