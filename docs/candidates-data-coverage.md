# Candidates: Data Coverage & Consistency

This document explains how candidate data is captured, stored, and displayed across the admin edit tool, public candidate pages, and the jury evaluation UI. It also documents the mapping for the “with_sections” Excel/CSV format and how categories and the overview (Überblick) are handled consistently.

## Scope
- Ensures the candidate admin editor exposes and persists all key fields.
- Unifies how Overview/Überblick and Categories appear on candidate and evaluation pages.
- Adds Article URL support to public/evaluation views.

## Data Model (at a glance)
- Table: `wp_mt_candidates`
- Top‑level fields used here: `name`, `slug`, `organization`, `position`, `country`, `linkedin_url`, `website_url`, `article_url`, `photo_attachment_id`, `post_id`, `import_id`, `description_sections` (JSON).

## `description_sections` Contract
The plugin reads the following keys. Multiple synonyms are supported to accommodate various import sources.

- Overview (Überblick)
  - Keys: `overview` (preferred), `ueberblick`, `überblick`, `uberblick`
  - Legacy fallback: `description`, `summary`
- Category
  - Keys: `category` (preferred), `award_category` (fallback)
  - Values: Free text; the admin uses three official German labels.
- Evaluation Criteria (combined rich text)
  - Key: `evaluation_criteria`
  - Parsing: Bold headers like “Mut & Pioniergeist:” etc. are parsed for display on the candidate page.
- Evaluation Criteria (structured, optional)
  - Keys: `mut_pioniergeist`, `innovationsgrad`, `umsetzungskraft_wirkung`, `relevanz_mobilitaetswende`, `vorbild_sichtbarkeit`

Example JSON:
```
{
  "overview": "Kurzbeschreibung ...",
  "category": "Start-ups, Scale-ups & Katalysatoren",
  "evaluation_criteria": "<strong>Mut & Pioniergeist:</strong> ...",
  "mut_pioniergeist": "…",
  "innovationsgrad": "…",
  "umsetzungskraft_wirkung": "…",
  "relevanz_mobilitaetswende": "…",
  "vorbild_sichtbarkeit": "…"
}
```

## Admin Edit Tool (What you see and how it saves)
- File: `Plugin/includes/admin/class-mt-candidates-admin.php`
- New/updated fields:
  - Category: `<select>` with the three official categories; saved to `description_sections.category`.
  - Overview (Überblick): Rich text; saved to `description_sections.overview` and mirrored to `description_sections.description` for backward compatibility.
  - Evaluation Criteria (combined): Rich text; saved to `description_sections.evaluation_criteria`.
  - Article URL: Saved to top‑level `article_url`.
- Prefill behavior: The form pre-fills Category, Overview, and Criteria from the candidate’s `description_sections` when editing.
- Existing top‑level fields remain (Name, Slug, Organization, Position, Country, LinkedIn, Website, Photo).

## Public Candidate Page
- Template: `templates/frontend/single/single-mt_candidate-enhanced-v2.php`
- Shows Overview from `description_sections.overview` (with fallbacks listed above).
- Shows Category (if present in `description_sections.category`).
- Shows links: LinkedIn, Website, Article (new). The Article link uses `article_url`.
- Still supports structured or combined criteria content for display.

## Evaluation Page (Jury)
- Template: `templates/frontend/jury-evaluation-form.php`
- Candidate header shows Organization, Position, Category (from `description_sections.category`).
- Shows Overview/Description consistently using the same keys and fallbacks as the public page.
- Shows LinkedIn, Website, and Article links when present.

## Jury Dashboard
- Template: `templates/frontend/jury-dashboard.php`
- Each candidate card reads Category from `description_sections.category` (or `award_category` fallback) and displays a badge. Cards remain filterable by category.

## Shortcodes & Filtering
- Renderer: `includes/public/renderers/class-mt-shortcode-renderer.php`
- `[mt_candidates_grid category="..."]` filters by `description_sections.category` via a safe LIKE condition on the repository. If using non‑English labels, pass them as stored in `category`.

## Repository Support
- File: `includes/repositories/class-mt-candidate-repository.php`
- Adds a safe `where` option for filtering `description_sections` via `LIKE` when needed by public renderers/shortcodes.

## Excel/CSV (“with_sections”) Mapping
When preparing data for import, ensure the following columns/keys map:
- Top‑level fields: `name`, `slug`, `organization`, `position`, `country`, `linkedin_url`, `website_url`, `article_url`.
- `description_sections` JSON should include at least: `overview`, `category`, and either `evaluation_criteria` (combined) or structured keys as listed above.
- Synonyms in Overview are supported; use `overview` preferred.

## Backward Compatibility
- Overview is mirrored to `description_sections.description` so older templates and exporters that expect `description` continue to work.
- Category continues to be read from either `category` or `award_category`.
- Candidate pages still parse either combined or structured criteria.

## Validation Steps
1. Admin → Mobility Trailblazers → Candidates → Edit:
   - Set Category, Overview, Evaluation Criteria, and Article URL. Save.
2. Public candidate page (`/candidate/{slug}/`):
   - Verify Overview (Überblick) appears, Category badge shows, and Article link is visible.
3. Jury dashboard:
   - Card shows Category badge; filtering by category works as expected.
4. Jury evaluation form:
   - Candidate details include Category; Overview is present; links (LinkedIn/Website/Article) display when set.
5. Shortcode check:
   - Place `[mt_candidates_grid category="Start-ups, Scale-ups & Katalysatoren"]` on a page; confirm only matching candidates render.

## Files Touched
- Admin editor form: `Plugin/includes/admin/class-mt-candidates-admin.php`
- Candidate page (enhanced v2): `Plugin/templates/frontend/single/single-mt_candidate-enhanced-v2.php`
- Evaluation form: `Plugin/templates/frontend/jury-evaluation-form.php`
- Candidate repository filtering: `Plugin/includes/repositories/class-mt-candidate-repository.php`

## Notes
- “Überblick” misspellings (e.g., “Uberclick”) are addressed by centralizing on `overview` with sensible fallbacks.
- No database schema changes were required; fields already exist.

