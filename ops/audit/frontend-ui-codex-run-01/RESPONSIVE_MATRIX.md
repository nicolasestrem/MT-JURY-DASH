Responsive Matrix (Read‑Only)

Scope: Not exhaustive; highlights primary breakpoints and components observed in CSS.

Breakpoints Observed
- 480px: Fine-grained phone tweaks in legacy candidate/profile CSS.
- 576px: Declared in legacy variables as `--mt-breakpoint-sm`.
- 768px: Primary mobile/tablet boundary across v4 and legacy.
- 992px: Legacy tablet-to-desktop boundary for candidate/profile.
- 1200px: Container and layout max-widths common across files.
- 1920px: Specific admin/jury dashboard tuning.

Key Components
- v4 Containers: `.mt-root` scoped, `.mt-container` with `max-width: var(--mt-container-max)` and media queries at 768px.
- Candidate Cards: `.mt-candidate-card*` with responsive image utilities and content spacing.
- Jury Dashboard: Multiple fixed `max-width: 1200px` sections; ensure nested grids don’t overflow at 768–992px.
- Tables: Rankings/table enhancements apply min-widths that may cause horizontal scroll at ≤768px.

Risks
- Mixed systems: v4 + legacy breakpoints lead to uneven tablet experiences (992px breakpoints from legacy vs 768px v4).
- Fixed max-widths: Repeated `max-width: 1200px` can fight v4 container sizing and cause wrapping issues inside page builders.

Recommendations
- Standardize on v4 breakpoints for public routes; replace legacy 992px blocks with 768/1200 tokens.
- Move fixed max-widths to container utilities; delegate component width via percentage/flex rules.
