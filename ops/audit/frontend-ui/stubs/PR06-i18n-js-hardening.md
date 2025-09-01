Title: PR06 — i18n for JS hardening (draft)

Scope
- Localize remaining UI strings; remove hardcoded messages in JS.
- Ensure MT_I18n_Handler provides all required keys.

Notes
- Stub for coordination; no code changes yet.

Risks
- Missing keys causing undefined UI text; provide robust defaults via localization.

Test Plan
- Switch locales and verify all messages translate; console stays clean in production.
