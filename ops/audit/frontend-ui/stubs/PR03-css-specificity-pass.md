Title: PR03 — CSS specificity pass (draft)

Scope
- Reduce !important in mt-candidate-grid.css and key hotfix files.
- Scope overrides under .mt-root and rely on v4 tokens/utilities.

Notes
- Report-only stub for coordination. No visual changes yet.

Risks
- Regressions where !important masked theme styles; mitigate with scoping and QA.

Test Plan
- Candidate grid across breakpoints; Elementor-heavy pages; theme conflict audit.
