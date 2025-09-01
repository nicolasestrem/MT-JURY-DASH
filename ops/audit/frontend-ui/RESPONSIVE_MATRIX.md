Responsive Matrix (Plugin)

Breakpoints observed

- 1400px: Candidate grid 5-columns → 4-columns
- 1200px: Grid columns reduce (4→3); various layout spacing adjustments
- 992px: Grid reduces to 2–3 columns; stat grids 2 columns
- 900px: Rankings item flex → column; compact controls
- 768px: Major mobile shift; stacked layouts; smaller images and fonts
- 480px: Single-column; reduced paddings; compact progress indicators

Key components affected

- Candidates Grid: Responsive across 1400/1200/992/768/480 with `grid-template-columns` changes.
- Candidate Showcase: Photo size and metadata layout adjust at 768/480; consider using `aspect-ratio`.
- Jury Dashboard: Rankings grid collapses progressively; scores input widths adjust.
- Evaluation Page: Section headers and form controls stack; actions become full-width.

Notes

- Several repeated breakpoint blocks exist across `Plugin/assets/css/frontend/_responsive.css`; consider consolidating and mapping onto v4 utilities/tokens for spacing and typography.
