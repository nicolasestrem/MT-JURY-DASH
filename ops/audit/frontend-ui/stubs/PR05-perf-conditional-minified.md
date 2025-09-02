Title: PR05 — Perf: conditional loading & minified (draft)

Scope
- Use minified assets in production; add route-conditional enqueues.
- Move dynamic tokens inline to 'mt-v4-tokens' handle.

Notes
- Stub for coordination; no code changes yet.

Risks
- Asset path/versioning and dependencies; ensure correct handle order.

Test Plan
- Verify minified assets in prod and source maps in dev; reduced waterfall on plugin routes.
