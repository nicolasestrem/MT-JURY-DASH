# CLAUDE.md

**Mobility Trailblazers WordPress Plugin v2.5.42** - Enterprise award management platform for DACH mobility innovators. 50 candidates, 24 jury members, complete evaluation system with 5-criteria scoring.

**URLs:** Production: https://mobilitytrailblazers.de/vote/ | Staging: http://localhost:8080/  
**Dates:** Launch Aug 18, 2025 | Ceremony Oct 30, 2025  
**Migration:** New voting platform at https://github.com/nicolasestrem/mobility-voting-app (PocketBase + SvelteKit)

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
| **CSS** | BEM methodology, CSS v4 tokens, Mobile-first, No !important |

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
| **CSS not loading** | Ensure v4 framework is registered: `assets/css/framework/` |
| **AJAX failing** | Check nonce: `wp_create_nonce('mt_admin_ajax')` |

## Architecture

### Core Patterns
- **Dependency Injection Container:** `MT_Container` manages service lifecycle
- **Service Providers:** `MT_*_Provider` for organized service registration
- **Repository Pattern:** `MT_*_Repository extends MT_Base_Repository` implements interfaces
- **Service Pattern:** `MT_*_Service` for business logic (DI-enabled)
- **AJAX Handlers:** `MT_*_Ajax extends MT_Base_Ajax` with nonce validation
- **Legacy Facade:** `mt_get_repository()`, `mt_get_service()` for backward compatibility

### Key Directories
- `includes/core/` - Container, Plugin class, Service Provider base
- `includes/providers/` - Service provider implementations  
- `includes/interfaces/` - Service and repository interfaces
- `includes/repositories/` - Data access layer (interface-based)
- `includes/services/` - Business logic layer (DI-enabled)
- `includes/ajax/` - AJAX handlers with base validation class
- `includes/admin/` - Admin interfaces and columns
- `includes/widgets/` - Dashboard widgets
- `includes/legacy/` - Backward compatibility layer
- `assets/css/` - CSS framework v4 (USE THIS - BEM methodology)
- `templates/` - PHP templates (admin/, frontend/)
- `tests/` - Consolidated test files (8 files, was 23)

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
- `mobility-trailblazers.php` - Main plugin file, bootstrap
- `includes/core/class-mt-container.php` - DI container
- `includes/core/class-mt-plugin.php` - Main plugin class
- `includes/repositories/class-mt-evaluation-repository.php` - Core evaluation data
- `includes/services/class-mt-evaluation-service.php` - Evaluation business logic
- `assets/css/framework/mobility-trailblazers-framework-v4.css` - Main CSS framework

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

---
*v2.5.42 | Sep 2025 | WordPress 5.8+ | PHP 7.4+ (8.2+ recommended)*
- 🔍 CLAUDE CODE - 8 HOUR DEEP AUDIT MISSION

- Never bypass pre commit hooks without approval