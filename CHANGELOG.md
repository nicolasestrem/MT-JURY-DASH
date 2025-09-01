# Changelog

All notable changes to the Mobility Trailblazers WordPress Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.5.43] - 2025-09-01

### Changed
- **CSS De-prioritized**: CSS development is now on hold due to visual regression issues
- **Removed CSS Quality Workflow**: Deleted strict CSS enforcement from CI/CD pipeline
- **StyleLint Rules Relaxed**: Changed all CSS linting rules from errors to warnings
- **CSS Framework v4**: Rollout postponed indefinitely

### Removed
- Removed `.github/workflows/css-quality.yml` workflow that blocked CSS with !important
- Removed strict CSS quality checks from pre-commit requirements

## [2.5.42] - 2025-08-31

### Security
- **CRITICAL**: Fixed SQL injection vulnerability in export function (CVE pending)
- **CRITICAL**: Fixed SQL injection vulnerability in audit log ORDER BY clause
- **HIGH**: Standardized nonce verification across all AJAX handlers
- **HIGH**: Implemented rate limiting for evaluation submissions (10/minute)
- **HIGH**: Implemented rate limiting for inline evaluation saves (20/minute)

### Fixed
- Fixed inconsistent security verification in AJAX handlers
- Fixed potential CSRF vulnerabilities in evaluation endpoints
- Resolved security issues identified in comprehensive audit

### Added
- Added `check_rate_limit()` method to base AJAX handler class
- Added security event logging for rate limit violations
- Added comprehensive security documentation in `/docs/SECURITY-PATCHES.md`

### Changed
- Updated all AJAX handlers to use standardized `verify_nonce()` method
- Improved SQL query construction to prevent injection attacks
- Enhanced error messages for security failures

### Technical Details
- Replaced direct SQL interpolation with parameterized queries
- Implemented CASE statement for ORDER BY to prevent injection
- Added integer casting for all ID parameters in database queries

## [2.5.41] - 2025-08-18

### Added
- Initial release of version 2.5.41
- Complete evaluation system with 5-criteria scoring
- Support for 50 candidates and 24 jury members
- Comprehensive admin dashboard
- German localization support

### Features
- Award management platform for DACH mobility innovators
- Jury evaluation system with draft/final submission states
- Bulk import/export functionality
- Real-time evaluation tracking
- Responsive design for mobile evaluation

## [2.5.40] - 2025-08-15

### Changed
- Pre-release testing and optimization
- Performance improvements for large datasets
- Database query optimization

## [2.5.39] - 2025-08-10

### Added
- Beta testing phase
- User acceptance testing feedback implementation
- UI/UX improvements based on jury feedback

---

For detailed security information about version 2.5.42, see [SECURITY-PATCHES.md](docs/SECURITY-PATCHES.md)