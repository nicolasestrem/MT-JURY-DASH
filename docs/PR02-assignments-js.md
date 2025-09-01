# PR02 — Assignments JS Consolidation (Non‑CSS)

## Summary
Consolidates assignments UI logic, hardens UX and accessibility, and standardizes error handling without touching CSS. Prevents double bindings, aborts in‑flight requests, and adds stable data-test selectors for E2E.

## Changes
- Namespaced event handlers and ownership flag (`window.MT_ASSIGNMENTS_OWNED`) to avoid duplicate bindings with admin.js.
- Modals: focus trap, ESC/overlay close, restore focus to trigger.
- AJAX: 15s timeout; abort in‑flight when closing or retrying; unified errors via `window.mtHandleAjaxError`.
- Notifications: fall back to `window.mtShowNotification` with alert fallback.
- Data-test hooks:
  - Buttons: `auto-assign-btn`, `manual-assign-btn`, `bulk-actions-btn`, `export-btn`, `clear-all-btn`, `bulk-apply-btn`, `bulk-cancel-btn`.
  - Selects/rows: `bulk-action-select`, `manual-jury-select`, `manual-candidate-checkbox`, `manual-assign-modal`, `manual-assign-form`.

## Debug Plan (≤10 minutes)
1) Admin → Assignments → Click Auto-Assign → Press ESC: modal closes; request aborts.
2) Manual Assign submit twice quickly: only one request visible in Network; no duplicate.
3) Throttle network; trigger a timeout: unified error notice appears.
4) Tab/Shift+Tab cycles within modal; ESC closes; focus returns.
5) Type rapidly in search: results update only after you pause (~250ms).

## Testing
- Playwright: use new data-test hooks for modal open/close, submit, and search debounce.
- Visuals: unchanged; ≤3% threshold preserved (CI config in PR #23).

## Risks
- Minimal: all CSS untouched; JS guarded by feature flags/ownership checks.

