# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Mobility Trailblazers WordPress Plugin v2.5.41** - Enterprise award management platform for DACH mobility innovators. 50 candidates, 24 jury members, complete evaluation system with 5-criteria scoring.

**URLs:** Production: https://mobilitytrailblazers.de/vote/ | Staging: http://localhost:8080/  
**Dates:** Launch Aug 18, 2025 | Ceremony Oct 30, 2025  
**Migration:** New voting platform at https://github.com/nicolasestrem/mobility-voting-app (PocketBase + SvelteKit)

## CSS Notice - Not a Priority

**⚠️ CSS development is currently de-prioritized due to visual regression issues.**
- Previous attempts to fix CSS resulted in significant visual regressions
- CSS framework v4 rollout is on hold
- Editing CSS files is allowed but not recommended
- Use JavaScript-based solutions for critical UI issues
- StyleLint rules changed to warnings (not errors)
- CSS quality workflow removed from CI/CD

## Critical Security Rules

### NEVER Commit (Must be in .gitignore)
- `.env`, `.env.local`, `.env.production` (passwords, API keys)
- `wp-config.php` (WordPress config with salts)
- `*.sql` (database dumps)
- `*credentials*`, `*password*` (sensitive files)
- `.claude/settings.local.json` (MCP settings)

### Safe to Commit
- `.env.example`, `wp-config-sample.php` (templates only)

## Git Workflow (NEVER commit to main)

### Branch Strategy
- `main` = production ready
- `develop` = integration branch  
- `feature/*` = active development

### Semantic Commits
Format: `type(scope): description`
- Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`
- Examples: `feat(evaluation): add bulk export` | `fix(jury): resolve calculation error`

### Quick Commands
```bash
# Start work
git checkout develop && git pull && git checkout -b feature/name

# Commit & deploy
git commit -m "type(scope): description"
git push origin feature/name
# Create PR → merge to develop → test → merge to main
```

## Common Development Commands

### Building & Minification
```bash
# Build all assets (CSS & JS)
npm run build

# Build only CSS
npm run build:css  

# Build only JS
npm run build:js

# Clean build (remove all minified files, then rebuild)
npm run build:clean && npm run build:prod
```

### Testing
```bash
# Run all Playwright E2E tests
npx playwright test

# Run tests with UI mode (interactive)
npx playwright test --ui

# Run specific test file or grep pattern
npx playwright test --grep="evaluation"

# Run tests with headed browser (see the browser)
npx playwright test --headed

# Run tests for specific project (admin, jury-member, mobile)
npx playwright test --project=admin
```

### Localization & Translation
```bash
# Analyze German translations
npm run i18n:analyze

# Extract translatable strings
npm run i18n:extract

# Compile translation files
npm run i18n:compile

# Validate translation deployment
npm run i18n:validate

# Full i18n check
npm run i18n:check
```

### Database & WordPress CLI
```bash
# Flush WordPress cache
wp cache flush

# Check custom tables
wp db query "SHOW TABLES LIKE 'wp_mt_%'"

# Rewrite rules flush (fixes 404 errors)
wp rewrite flush

# Import candidates from CSV
wp mt import-candidates --file=candidates.csv
```

## Pre-Commit Requirements (Run Before EVERY Commit)

| Check | Command |
|-------|---------|
| PHP Syntax | `find . -name "*.php" -exec php -l {} \;` |
| Debug Code | `grep -r "console.log\|var_dump\|print_r" --include="*.php" --include="*.js"` |
| Sensitive Data | `grep -r "password\|api_key\|secret" --include="*.php" --include="*.js"` |
| PHP Standards | `./vendor/bin/phpcs --standard=WordPress .` |
| JS Linting | `npm run lint` |
| E2E Tests | `npx playwright test` |

**Pre-commit hook:** See `/docs/git-hooks.md` for installation script.

## Claude Code Agents & MCP Servers

### Recommended Agents (Deploy in parallel)

| Category | Agent | Use For |
|----------|-------|---------|
| **WordPress** | `wordpress-code-reviewer` | Plugin code review, WP best practices |
| | `security-audit-specialist` | SQL injection, XSS vulnerabilities |
| | `localization-expert` | German translations, i18n |
| **Frontend** | `frontend-ui-specialist` | CSS/JS optimization, responsive design |
| | `fullstack-dev-expert` | Features spanning frontend/backend |
| **Quality** | `syntax-error-detector` | Post-coding syntax checks |
| | `code-refactoring-specialist` | Structure improvements |
| | `documentation-writer` | Feature documentation |
| **Management** | `project-manager-coordinator` | Development planning |
| | `product-owner-manager` | Requirements, prioritization |

### Available MCP Servers

| Category | Server | Purpose |
|----------|--------|---------|
| **Database** | `mcp__mysql` | Direct DB queries, table management |
| | `mcp__docker` | Container management |
| | `mcp__wordpress` | WP-CLI commands |
| **Files** | `mcp__filesystem` | File operations |
| | `mcp__git` | Git operations |
| **Tools** | `mcp__kapture` | Screen capture |
| | `mcp__knowledge` | Knowledge graph |

## Testing & Quality

### Testing Commands
- All tests: `npx playwright test --config=playwright.config.ts`
- Specific: `npx playwright test --grep="evaluation"`
- UI mode: `npx playwright test --ui`
- Build assets: `npm run build` (minifies CSS/JS)
- Clean build: `npm run build:clean && npm run build:prod`

### Coverage Requirements
- **Minimum:** 80% for new code
- **100% Coverage:** Payment, auth, evaluation submission, vote counting, data export

### Standards
| Language | Requirements |
|----------|-------------|
| **PHP** | WPCS via PHPCS, Type hints, PHPDoc, Prepared statements |
| **JavaScript** | ES6+, JSDoc, No jQuery, Error boundaries |
| **CSS** | *Not a priority* - Editing allowed but CSS v4 rollout on hold due to visual regression issues |

## Performance Targets
- Page Load: <2s | Memory: <64MB | Queries: <50/page
- JS Bundle: <200KB | CSS Bundle: <50KB

## Development Setup

### Debug Configuration (wp-config.php)
```php
define('WP_DEBUG', true);
define('MT_DEBUG', true);
define('SAVEQUERIES', true);
```

### Debugging (Use MT_Debug, NOT var_dump)
```php
// Correct
MT_Debug::log('Label', $data);
error_log('MT Debug: ' . print_r($data, true));

// Wrong - Remove before commit
var_dump($data); console.log(data);
```

## Common Issues & Solutions

| Issue | Solution |
|-------|---------|
| **Changes not reflecting** | `wp cache flush && npm run build` |
| **Database errors** | `wp db query "SHOW TABLES LIKE 'wp_mt_%'"` |
| **Assignments broken** | `wp rewrite flush && wp post-type list` |
| **Rankings page 404** | Known issue - check route/controller wiring in `includes/routes/` |
| **WP-CLI not working** | Known production issue - check registration in `includes/cli/` |
| **CSS not loading** | CSS fixes not prioritized - use JavaScript workarounds if critical |
| **AJAX failing** | Check nonce: `wp_create_nonce('mt_admin_ajax')` |

## Architecture Overview

This WordPress plugin follows a modern service-oriented architecture with dependency injection, making it testable and maintainable while remaining compatible with WordPress patterns.

### Core Patterns
- **Dependency Injection Container:** `MT_Container` manages service lifecycle
- **Service Providers:** `MT_*_Provider` for organized service registration
- **Repository Pattern:** `MT_*_Repository extends MT_Base_Repository` implements interfaces
- **Service Pattern:** `MT_*_Service` for business logic (DI-enabled)
- **AJAX Handlers:** `MT_*_Ajax extends MT_Base_Ajax` with nonce validation
- **Legacy Facade:** `mt_get_repository()`, `mt_get_service()` for backward compatibility

### Key Directories
- `Plugin/` - Main plugin directory containing all PHP code
- `Plugin/includes/core/` - Container, Plugin class, Service Provider base
- `Plugin/includes/providers/` - Service provider implementations  
- `Plugin/includes/interfaces/` - Service and repository interfaces
- `Plugin/includes/repositories/` - Data access layer (interface-based)
- `Plugin/includes/services/` - Business logic layer (DI-enabled)
- `Plugin/includes/ajax/` - AJAX handlers with base validation class
- `Plugin/includes/admin/` - Admin interfaces and columns
- `Plugin/includes/widgets/` - Dashboard widgets
- `Plugin/includes/legacy/` - Backward compatibility layer
- `Plugin/includes/elementor/` - Elementor widget integrations
- `Plugin/includes/fixes/` - Targeted bug fixes and patches
- `assets/css/` - CSS files (v4 rollout on hold - editing allowed but not prioritized)
- `Plugin/templates/` - PHP templates (admin/, frontend/)
- `scripts/` - Build and utility scripts
- `docs/` - Documentation files

## Deployment

### Pre-Deployment Checklist
- [ ] Tests pass (`npx playwright test`)
- [ ] No debug code (`grep -r "console.log"`)
- [ ] Version bumped
- [ ] CHANGELOG.md updated

### Deploy Commands
```bash
git tag -a v2.0.1 -m "Release: description"
git push staging main && git push production main
wp cache flush
```

### Post-Deployment
- [ ] Verify critical paths | [ ] Check error logs | [ ] Monitor metrics

## Support

**Debug Issues:** Admin → MT Award System → Debug Center  
**Logs:** `/wp-content/debug.log`  
**Detailed Examples:** See `/docs/` directory  
**Repository:** https://github.com/nicolasestrem/mobility-trailblazers

## Critical Files & Components

### Core System Files
- `Plugin/mobility-trailblazers.php` - Main plugin file, bootstrap
- `Plugin/includes/core/class-mt-container.php` - DI container  
- `Plugin/includes/core/class-mt-plugin.php` - Main plugin class
- `Plugin/includes/repositories/class-mt-evaluation-repository.php` - Core evaluation data
- `Plugin/includes/services/class-mt-evaluation-service.php` - Evaluation business logic

### Database Tables
- `wp_mt_evaluations` - 5-criteria scores (0-10, 0.5 increments)
- `wp_mt_jury_assignments` - Jury-candidate relationships  
- `wp_mt_audit_log` - Activity tracking
- `wp_mt_error_log` - Error logging

### Custom Post Types
- `mt_candidate` - Candidate profiles
- `mt_jury_member` - Jury member profiles

### Development Rules
1. **ALWAYS** check for existing functionality before implementing
2. **ALWAYS** use Repository-Service pattern for data/logic
3. **ALWAYS** verify nonces in AJAX handlers
4. **NEVER** use direct database queries - use repositories
5. **NEVER** remove features without explicit confirmation
6. **ALWAYS** update version in ALL locations when releasing

## Key Implementation Details

### Dependency Injection Container
The plugin uses a custom lightweight DI container (`MT_Container`) that:
- Auto-resolves dependencies through reflection
- Supports singleton and transient bindings
- Registers services through provider classes
- Compatible with WordPress's global function patterns via facade methods

### AJAX Handler Pattern
All AJAX handlers extend `MT_Base_Ajax` which provides:
- Automatic nonce verification
- Permission checking
- Standardized error responses
- File upload validation

### Repository Pattern
All data access goes through repository interfaces:
- Abstracts database queries
- Provides testable interfaces
- Handles caching automatically
- Uses WordPress's `$wpdb` internally

### Service Layer
Business logic is encapsulated in service classes:
- Injected dependencies via constructor
- No direct database access
- Handles validation and business rules
- Orchestrates between repositories

---
*v2.5.41 | Sep 2025 | WordPress 5.8+ | PHP 7.4+ (8.2+ recommended)*
- 🔍 CLAUDE CODE - PROJECT INITIALIZED ON 2025-09-03

- Never bypass pre commit hooks without approval