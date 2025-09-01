# JS REPORT

Error Handling & AJAX

- Most AJAX calls include `nonce` and use localized endpoints (`mt_admin.ajax_url` or `mt_ajax.ajax_url`). Success/fail handlers exist but vary in message consistency.
- Example good pattern: `Plugin/assets/js/admin.js` (refreshDashboardWidget) includes nonce, success/error/complete, and visual state.
- Some POSTs (e.g., `Plugin/assets/js/frontend.js` submitEvaluation) use `$.post(...).done(...).fail(...)`; this is acceptable but standardizing to `$.ajax` with explicit `error` and `complete` can improve consistency and timeout handling.
- Recommendation: Centralize AJAX helpers (timeout, error messaging, cancellation via `AbortController` or `jqXHR.abort()`), reuse across modules.

Console Logs

- `Plugin/assets/js/mt-modal-debug.js`: Multiple `console.log` statements at lines 5, 38, 45, 79–88, 96, 130, 147–148.
  - Suggestion: Guard behind a `window.MT_DEBUG` flag or remove in production; ensure the admin template only includes this in non-production.
- `Plugin/assets/js/mt-jury-filters.js`: Logs at 10–113 (load, handlers, filters, counts).
  - Suggestion: Remove or guard with debug flag.
- `Plugin/assets/js/frontend.js`: One `console.log` at ~711 (“Evaluation submission already in progress”).
  - Suggestion: Switch to a UI notice or remove.
- `Plugin/templates/admin/assignments.php`: Inline fallback JS logs/alerts; plus enqueues `mt-modal-debug.js` unconditionally.
  - Suggestion: Remove inline fallback in favor of centralized `mt-assignments.js`; guard debug assets behind `WP_DEBUG` and `window.MT_DEBUG`.
 - `Plugin/assets/js/mt-event-manager.js`: Debug interval logs based on `window.MT_DEBUG`, but a console statement is malformed in `trackEvents()`; review to avoid runtime errors when debug is on.

Event Binding & Potential Leaks

- Overlapping bindings on assignments:
  - `Plugin/assets/js/admin.js`: Delegated `$(document).on('click', '#mt-auto-assign-btn' | '#mt-manual-assign-btn' | '#mt-clear-all-btn' | '.mt-remove-assignment', ...)` (e.g., lines 237, 242, 257, 262).
  - `Plugin/assets/js/mt-assignments.js`: Direct `.off().on('click', ...)` for the same selectors (lines 18, 23, 28, 33, 49, 54, 59, 64).
  - Risk: Double-fire if both scripts are active; `.off()` in one file won’t detach delegated handlers set in the other.
  - Fix: Namespace events (e.g., `.on('click.mt-assign', ...)`) and ensure only one script binds on the assignments page (feature flag or page guard). Prefer one owner module.
- Intervals: `Plugin/assets/js/frontend.js` stores intervals on `window.mtIntervals` and clears previous intervals; good practice to minimize leaks.
- Global handlers: Several `$(document).ready(...)` blocks across files; prefer single init per screen/module to avoid re-inits after partial DOM updates.
- Mixed native + jQuery in modal scripts (`mt-modal-force.js` uses `DOMContentLoaded` and jQuery ready in same file), increasing the chance of duplicate binds. Prefer one pattern and idempotent initializers.
 - Centralized event manager (`mt-event-manager.js`) is present and supports namespacing/cleanup. Prefer routing bindings through it to reduce duplicate `$(document).on` scattered across files.

Async/UX Notes

- Submission double-click guards exist (`isSubmittingEvaluation`); good.
- Consider adding request timeouts and explicit retry/cancel flows for longer admin tasks (imports/assignments).
- For modal interactions, ensure `aria-*` attributes and focus trapping for accessibility; some helpers exist but are not consistent across all modals.
