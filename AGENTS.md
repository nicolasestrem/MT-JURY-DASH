# Repository Guidelines

## Project Structure & Modules
- `includes/`: Core PHP plugin code (core, admin, ajax, repositories, Elementor, public renderers).
- `Plugin/`: Packaged plugin for distribution (templates, assets, includes).
- `Production Source Code/`: Snapshot of production-ready code and assets.
- Assets: `Plugin/assets` and `Production Source Code/assets` for CSS/JS.
- `docs/`: Architecture, AJAX API, backend reference, CLI commands, CSS audit.
- `TB/`: Docker Compose WordPress environment; see `TB/README.md`.
- `scripts/`: Utilities for i18n, maintenance, and test helpers. E2E tests in `tests/e2e`.

## Build, Test, and Development Commands
- Local env: `cd TB && docker-compose up -d` (starts WordPress + WP-CLI).
- Activate plugin: `docker-compose exec wpcli wp plugin activate mobility-trailblazers`.
- E2E tests: `npm test` (Playwright). UI mode: `npx playwright test --ui`.
- Targeted runs: `npm run test:staging` / `npm run test:production`.
- Assets: `npm run build` (all), `npm run build:css`, `npm run build:js`.
- i18n: `npm run i18n:validate` (see other `i18n:*` scripts).

## Coding Style & Naming Conventions
- PHP: WordPress Coding Standards; 4-space indent; one class per file.
- Filenames: `class-mt-*.php` (e.g., `class-mt-service-provider.php`).
- JS/CSS: `mt-*.js` / `mt-*.css`; BEM class names prefixed `mt-`.
- Lint: `./vendor/bin/phpcs --standard=WordPress .` and `npx stylelint "**/*.css"`.
- Security: Always check nonces/capabilities in AJAX; validate input; escape on output.

## Testing Guidelines
- Framework: Playwright (`playwright.config.ts`); tests under `tests/e2e`.
- Local data: `scripts/setup-test-users.sh` for Docker-based E2E setup.
- Optional PHP unit: `./vendor/bin/phpunit` if configured.
- Prefer feature-focused test names; cover critical flows and permissions.

## Commit & Pull Request Guidelines
- Branches: `main` (production), `develop` (integration), topic branches (`feature/*`, `css-audit-v4`, etc.).
- Conventional Commits (lowercase types): `feat|fix|docs|style|refactor|test|chore|build|ci|perf|revert`.
- Scopes: `security`, `docker`, `css`, `evaluation`, `jury`, `admin`.
- Examples: `feat(evaluation): add bulk export`, `fix(security): sanitize order by`.
- PRs: merge topic → `develop`; include description, linked issues (`#123`), verify steps, and UI screenshots when relevant.
- Required checks: `npm test`, `npm run i18n:validate`, `npm run build:prod`, `./vendor/bin/phpcs`, `npx stylelint`.

## Security & Configuration
- Secrets via `.env`/`TB/.env.example`; never commit credentials.
- Enable `WP_DEBUG` in development to load unminified assets.
- Use prepared statements; validate and escape all inputs.

