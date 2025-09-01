Title: PR02 — Assignments JS consolidation (draft)

Scope
- Move all modal/actions logic into assets/js/mt-assignments.js with namespaced events; remove inline fallback from template.
- Guard mt-modal-debug.js behind WP_DEBUG and window.MT_DEBUG.

Notes
- Report-only stub for coordination. No functional changes yet.

Risks
- Double bindings if admin.js also attaches; audit and gate admin.js on page.

Test Plan
- Admin > Assignments: auto-assign, manual assign, clear all, bulk actions — single-fire handlers.
