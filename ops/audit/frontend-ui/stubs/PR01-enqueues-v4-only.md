Title: PR01 — Centralize v4 enqueues (draft)

Scope
- Make MT_Public_Assets the single owner of v4 CSS; gate legacy on v4 routes.
- Remove v4 enqueues from class-mt-plugin.php; optionally dequeue legacy via get_legacy_css_handles().

Notes
- Report-only stub: no source changes yet. For coordination across agents.

Risks
- Missing styles on non-plugin routes if legacy required; mitigate via route gating and mt_enable_css_v4 filter.

Test Plan
- Jury dashboard, candidate grid/single, evaluation. Ensure only v4 CSS on plugin routes, no duplication.
