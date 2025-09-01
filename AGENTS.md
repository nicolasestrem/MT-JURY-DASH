# Repository Guidelines

## Project Structure & Modules
- `includes/`: Core PHP plugin code (core, admin, ajax, repositories, Elementor, public renderers).
- `Plugin/`: Packaged plugin (templates, assets, includes) used for distribution.
- `Production Source Code/`: Snapshot of production-ready code and assets.
- `assets/`: CSS/JS live under `Plugin/assets` and `Production Source Code/assets`.
- `docs/`: Architecture, AJAX API, backend reference, CLI commands, CSS audit.
- `TB/`: Docker Compose for local WordPress; see `TB/README.md`.
- `scripts/`: Utilities for i18n, maintenance, and test helpers.

## Build, Test, and Development
- Local env: `cd TB && docker-compose up -d` (WordPress + WP-CLI).
- Activate plugin: `docker-compose exec wpcli wp plugin activate mobility-trailblazers`.
- E2E tests: `npm test` (Playwright). UI mode: `npx playwright test --ui`.
- Targeted runs: `npm run test:staging` / `npm run test:production`.
- Assets: `npm run build`, or scoped: `npm run build:css`, `npm run build:js`.
- i18n checks: `npm run i18n:validate` (see other `i18n:*` scripts).

## Coding Style & Naming
- PHP: WordPress Coding Standards; 4-space indent; one class per file.
- Filenames: `class-mt-*.php` (e.g., `class-mt-service-provider.php`).
- JS/CSS: `mt-*.js` / `mt-*.css`; BEM-style class names prefixed `mt-`.
- Lint: `./vendor/bin/phpcs --standard=WordPress .`; CSS: `npx stylelint "**/*.css"`.
- Security: Always check nonces/capabilities in AJAX; escape on output.

## Testing Guidelines
- Framework: Playwright (`playwright.config.ts`, tests under `tests/e2e`).
- Commands: `npm test`, `npm run test:headed`, `npm run show-report`.
- Test data: `scripts/setup-test-users.sh` for local E2E setup (Docker).
- PHP unit tests (if configured): `./vendor/bin/phpunit`.

## Commit & Pull Requests
- Branching: `main` (production), `develop` (integration), topic branches (`feature/*`, `css-audit-v4`, etc.). Never commit to `main`.
- Commits: Conventional Commits `type(scope): description` (use lowercase types: `feat|fix|docs|style|refactor|test|chore|build|ci|perf|revert`).
- Scopes: `security`, `docker`, `css`, `evaluation`, `jury`, `admin` (match folder/module).
- Examples: `feat(evaluation): add bulk export`, `fix(security): sanitize order by`, `docs(config): add .env.example`.
- PRs: Merge topic → `develop`. Provide description, linked issues (`#123`), verify steps, and UI screenshots when relevant.
- Checks: `npm test`, `npm run i18n:validate`, `npm run build:prod`, and `./vendor/bin/phpcs` (and `npx stylelint`) must pass.
- Releases: bump SemVer and update `CHANGELOG.md` (Keep a Changelog) before merging to `main`.

## Security & Configuration
- Secrets: Use `.env`/`TB/.env.example`; never commit credentials.
- Dev mode: enable `WP_DEBUG` to load unminified assets.
- Data safety: use prepared statements; validate/escape all inputs.
