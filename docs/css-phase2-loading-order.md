# CSS Phase 2 Loading Order Documentation

**Date:** August 30, 2025  
**Branch:** feature/css-phase2-rebuild  
**Status:** Active Development

## Current CSS Loading Order (Migration Mode)

When `MT_CSS_VERSION = 'migration'` is set in wp-config.php:

1. **V4 Framework Base Files:**
   - `mt-v4-tokens.css` - CSS variables and design tokens
   - `mt-v4-reset.css` - Browser reset styles
   - `mt-v4-base.css` - Base element styles

2. **Phase 2 Consolidated File:**
   - `mt-phase2-consolidated.css` - All critical styles (865 lines, ~650 !important)

3. **JavaScript Files:**
   - Common scripts loaded via `enqueue_common_scripts()`

## CSS File Statistics

| File | Lines | !important Count | Purpose |
|------|-------|------------------|---------|
| mt-phase2-consolidated.css | 865 | ~650 | Complete functional styles |
| mt-emergency-consolidated-temp.css | 167 | 167 | Phase 1 minimal (deprecated) |

## Consolidation Coverage

The Phase 2 consolidated file includes critical styles from:

### Layout & Grid Systems
- Candidates grid (responsive)
- Dashboard layout
- Container system
- Flexbox utilities

### Components
- Candidate cards
- Evaluation forms
- Jury dashboard
- Rankings tables
- Modal dialogs
- Buttons and CTAs

### Form Elements
- Input fields
- Textareas
- Select dropdowns
- Score inputs
- Form validation states

### Responsive Breakpoints
- 1200px - Large desktop
- 992px - Desktop
- 768px - Tablet
- 576px - Mobile

### Typography
- Headings (h1-h6)
- Body text
- Links
- Lists

### Utility Classes
- Text alignment
- Spacing (margin/padding)
- Display properties
- Visibility

## Feature Flag Configuration

```php
// wp-config.php settings for Phase 2
define('MT_CSS_VERSION', 'migration');        // Activates Phase 2 CSS
define('MT_CSS_DEBUG', true);                  // Shows debug info
define('MT_CSS_PERFORMANCE_MONITOR', true);    // Tracks performance
define('MT_CSS_FORCE_LEGACY', false);         // Emergency rollback
```

## Loading Performance

| Metric | Phase 1 | Phase 2 | Improvement |
|--------|---------|---------|-------------|
| CSS Files Loaded | 52 | 4 | -92% |
| Total Requests | 52 | 4 | -92% |
| Parse Time (est) | 234ms | ~80ms | -66% |
| !important Count | 3,846 | ~650 | -83% |

## Rollback Procedure

To rollback to the old system:

### Option 1: Use Legacy CSS
```php
define('MT_CSS_VERSION', 'v3');
```

### Option 2: Force Legacy Mode
```php
define('MT_CSS_FORCE_LEGACY', true);
```

### Option 3: Git Rollback
```bash
git checkout main
```

## Next Steps

1. **Hour 2:** Setup monitoring and tooling
2. **Hour 3-4:** BEM conversion of components
3. **Hour 5:** Component isolation
4. **Hour 6:** Testing and validation

## Notes

- The staging site should now be functional with the Phase 2 consolidated CSS
- Visual appearance will be different from production but all elements should work
- Further refinement needed to remove remaining !important declarations
- BEM methodology will be applied in next phase