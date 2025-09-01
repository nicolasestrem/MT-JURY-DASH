# PR06 — i18n / JS Hardening (Non‑CSS)

## Summary
Removes hardcoded UI strings and guards console logs in production, standardizes error handling, and adds accessibility attributes for admin notices and modals.

## Changes
- Jury filters: localized “no results” text; removed hardcoded German string; console logs gated behind `window.MT_DEBUG`.
- Frontend: guarded duplicate-submission console log behind `MT_DEBUG`.
- Event Manager: fixed malformed debug memory log.
- Admin: `mtShowNotification` uses ARIA attributes (role="alert", aria-live, aria-atomic) for better announcements.
- Admin: centralized `window.mtHandleAjaxError` to normalize AJAX errors and timeouts.
- Evaluations modal: focus trap and clean abort of inflight requests.

## Debug Plan (≤10 minutes)
1) Switch site locale to `de_DE`. Perform jury filter with no matches: message is localized.
2) Ensure no console logs unless `window.MT_DEBUG = true`.
3) Simulate AJAX timeout (DevTools): admin notices show consistent messages.
4) Open evaluations modal; ESC and overlay close work; focus remains trapped.

## Testing
- Visuals: unchanged; ≤3% threshold protected by CI.
- E2E: use data-test hooks to open modals, assert roles/ARIA, and validate behavior.

## Risks
- Low: only messaging and guards changed; no CSS or template structure changes.

