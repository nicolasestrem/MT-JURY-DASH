# Jury Dashboard Increments — Whole Numbers

## Summary
To align evaluation behavior with desired UX, the Jury Dashboard increment/decrement controls now adjust scores by 1 (previously 0.5). This change is limited to JavaScript logic and does not modify CSS.

## Changes
- `Plugin/assets/js/frontend.js`: inline score buttons increase/decrease by 1.
- `Plugin/assets/js/table-rankings-enhancements.js`: `CONFIG.scoreStep` set to 1 and change indicators adapted to hide decimals when step is integer.

## Rationale
- Simplifies scoring interactions and avoids fractional confusion during quick reviews.
- Preserves 0–10 bounds and continues to support direct numeric input if needed.

## Debug Plan
1) On the jury dashboard, click +/−. Values change by 1.
2) Attempt to exceed bounds: values clamp at 0 or 10.
3) In rankings table, use +/− keys: steps are 1 and indicator shows +1/−1 without decimals.

