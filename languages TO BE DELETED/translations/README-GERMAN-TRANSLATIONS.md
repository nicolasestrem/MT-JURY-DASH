# German Translation Guide for Mobility Trailblazers

## Current Status
- **Total Strings**: 1,348
- **Translated**: 299 (22.2%)
- **Remaining**: 1,049 (77.8%)
- **Priority Completed**: All 30 frontend strings ✅
- **Critical Completed**: 103 evaluation/jury strings ✅

## Translation Files Structure

```
languages/
├── mobility-trailblazers.pot          # Source template (English)
├── mobility-trailblazers-de_DE.po     # German translations
├── mobility-trailblazers-de_DE.mo     # Compiled binary (WordPress uses this)
├── backups/                           # Automatic backups
└── translations/                      # Working translation files
    ├── frontend-priority-de_DE.json   # 30 frontend strings (COMPLETED)
    ├── frontend-priority-de_DE.csv    # CSV version for review
    ├── evaluation-jury-de_DE.json     # 114 jury interface strings (COMPLETED)
    ├── admin-critical-de_DE.json      # Critical admin strings
    └── README-GERMAN-TRANSLATIONS.md  # This file
```

## Quick Start

### 1. Import Existing Translations
```bash
# Import all prepared translations
cd C:\Users\nicol\Desktop\mobility-trailblazers
php scripts/import-german-translations.php

# Preview changes first (dry run)
php scripts/import-german-translations.php --dry-run
```

### 2. Check Translation Status
```bash
# Analyze current translation coverage
php scripts/german-translation-automation.php analyze
```

### 3. Process Remaining Strings
```bash
# Extract untranslated strings
php scripts/german-translation-automation.php extract

# Generate batch translation templates
php scripts/batch-translate-remaining.php
```

## Translation Guidelines

### 1. Formal Address (Sie Form)
**ALWAYS use formal "Sie" form** - never informal "Du"

✅ Correct:
- "Bitte geben Sie Ihre Bewertung ab"
- "Sind Sie sicher?"
- "Ihre Bewertung wurde gespeichert"

❌ Wrong:
- "Bitte gib deine Bewertung ab"
- "Bist du sicher?"
- "Deine Bewertung wurde gespeichert"

### 2. German Capitalization Rules
- **Nouns**: Always capitalize (Kandidat, Bewertung, Dashboard)
- **Verbs**: Lowercase unless at sentence start
- **Adjectives**: Lowercase (gut, erfolgreich, neu)

### 3. Common Translations

| English | German (Formal) |
|---------|----------------|
| Submit | Absenden |
| Save | Speichern |
| Cancel | Abbrechen |
| Delete | Löschen |
| Edit | Bearbeiten |
| View | Anzeigen |
| Dashboard | Dashboard |
| Settings | Einstellungen |
| Evaluation | Bewertung |
| Candidate | Kandidat |
| Jury Member | Jurymitglied |
| Assignment | Zuweisung |
| Score | Punktzahl |
| Rating | Bewertung |
| Comments | Kommentare |
| Draft | Entwurf |
| Completed | Abgeschlossen |
| Pending | Ausstehend |
| Success | Erfolg |
| Error | Fehler |
| Loading | Wird geladen |
| Processing | Wird verarbeitet |

### 4. The 5 Evaluation Criteria (CRITICAL)

These are the core criteria that MUST be translated consistently:

1. **Mut** (Courage)
   - Full: "Mut & Pioniergeist"
   - Description: "Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten"

2. **Innovation** (Innovation)
   - Full: "Innovationsgrad"
   - Description: "Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen"

3. **Umsetzungsstärke** (Implementation Strength)
   - Full: "Umsetzungskraft & Wirkung"
   - Description: "Fähigkeit zur Umsetzung und realer Einfluss der Initiativen"

4. **Relevanz für Mobilitätswende** (Relevance for Mobility Transformation)
   - Full: "Relevanz für die Mobilitätswende"
   - Description: "Bedeutung und Beitrag zur Transformation der Mobilität"

5. **Strahlkraft** (Visibility/Charisma)
   - Full: "Vorbildfunktion & Sichtbarkeit"
   - Description: "Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor"

### 5. Placeholders and Variables

Preserve all placeholders exactly as they appear:
- `%s` - String placeholder
- `%d` - Number placeholder
- `%1$s`, `%2$d` - Positioned placeholders
- HTML tags: `<strong>`, `<em>`, `<a>`, etc.

Example:
- English: "You have %d pending evaluations"
- German: "Sie haben %d ausstehende Bewertungen"

### 6. UI Space Constraints

Keep translations concise for UI elements:
- Button labels: Max ~15 characters
- Menu items: Max ~20 characters
- Form labels: Clear but brief

## Manual Translation Process

### For CSV Files:
1. Open in Excel or Google Sheets
2. Fill in the "Translation (German)" column
3. Save as CSV (UTF-8 encoding)
4. Import using the script

### For JSON Files:
1. Edit the "translations" section
2. Keep the structure intact
3. Use UTF-8 encoding
4. Validate JSON syntax before importing

### For Template Files:
1. Replace [TRANSLATE] with German translation
2. Review suggestions if provided
3. Save with UTF-8 encoding
4. Convert to CSV or JSON for import

## Quality Checklist

Before finalizing translations:

- [ ] All strings use formal "Sie" form
- [ ] German noun capitalization is correct
- [ ] Placeholders (%s, %d) are preserved
- [ ] HTML tags are intact
- [ ] Special characters (ä, ö, ü, ß) display correctly
- [ ] Translations fit UI space constraints
- [ ] Terminology is consistent throughout
- [ ] The 5 evaluation criteria are correctly translated
- [ ] Error messages are clear and actionable
- [ ] Date/time formats follow German conventions (DD.MM.YYYY)

## Testing Translations

### 1. WordPress Testing:
```php
// Switch to German in wp-config.php
define('WPLANG', 'de_DE');

// Or in WordPress Admin:
// Settings → General → Site Language → Deutsch
```

### 2. Test Key Areas:
- [ ] Jury Dashboard (`/vote/`)
- [ ] Evaluation Form
- [ ] Candidate Profiles
- [ ] Admin Dashboard
- [ ] Assignment Management
- [ ] Email Notifications

### 3. Common Issues:
- **Missing translations**: Check if MO file is compiled
- **Wrong encoding**: Ensure UTF-8 throughout
- **Broken placeholders**: Verify %s, %d are preserved
- **Layout breaks**: Shorten translations if needed

## Automation Scripts

### 1. `german-translation-automation.php`
Main automation script with commands:
- `analyze` - Check translation status
- `extract` - Export untranslated strings
- `translate` - Use DeepL API (requires key)
- `import` - Import translations from files
- `compile` - Generate MO file
- `validate` - Check translation quality
- `full` - Run complete workflow

### 2. `import-german-translations.php`
Imports prepared translation files:
- Reads JSON and CSV files
- Updates PO file
- Compiles MO file
- Creates backups

### 3. `batch-translate-remaining.php`
Processes remaining strings:
- Pattern matching for common UI elements
- Generates manual translation templates
- Creates review files

## DeepL API Integration (Optional)

To enable automatic translation:

```bash
# Set API key (get from deepl.com)
export DEEPL_API_KEY='your-api-key-here'

# Run translation
php scripts/german-translation-automation.php translate
```

Note: Manual review is still recommended for quality.

## File Formats

### PO File Format:
```
msgid "Original English"
msgstr "German Translation"
```

### JSON Format:
```json
{
  "Original English": "German Translation"
}
```

### CSV Format:
```
Priority,Category,Original,Translation,Context,References
1,Frontend,"Submit","Absenden",,templates/form.php:45
```

## Support and Resources

- WordPress i18n: https://developer.wordpress.org/plugins/internationalization/
- German Typography: https://de.wikipedia.org/wiki/Deutsche_Rechtschreibung
- DeepL API: https://www.deepl.com/docs-api
- PO/MO Editor: https://poedit.net/

## Progress Tracking

### ✅ Completed (Priority 1):
- All 30 frontend strings
- 73 evaluation/jury interface strings
- 30 critical admin strings

### 🔄 In Progress (Priority 2):
- Admin interface (734 strings)
- System messages
- Email templates

### 📋 Pending (Priority 3):
- Debug messages (101 strings - can remain English)
- Help documentation
- Extended descriptions

## Contact

For questions about translations:
- Project: Mobility Trailblazers
- Domain: mobilitytrailblazers.de
- Text Domain: `mobility-trailblazers`

---

*Last Updated: 2025-08-30*
*Translation Coverage: 22.2% (299/1348)*