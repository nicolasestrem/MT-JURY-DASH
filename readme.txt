=== Mobility Trailblazers ===
Contributors: nicolasestrem
Tags: award, management, jury, evaluation, voting
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 4.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Award management platform for recognizing mobility innovators in the DACH region.

== Description ==

Mobility Trailblazers is an enterprise-grade WordPress plugin designed to manage awards and recognition programs. It provides a comprehensive platform for managing candidates, jury members, evaluations, and the complete award selection process.

**Key Features:**

* **Candidate Management**: Manage 100+ candidate profiles with detailed information, photos, and categories
* **Jury System**: Advanced jury member management with role-based permissions and secure evaluation submission
* **Evaluation Framework**: Comprehensive 5-criteria evaluation system with scoring from 0-10 in 0.5 increments
* **Import/Export**: Powerful CSV and Excel import capabilities with photo management
* **Multi-language**: Full German translation support with professional localization
* **Modern Architecture**: Built with dependency injection, service providers, and modern PHP patterns
* **Security**: Enterprise-grade security with nonce verification, capability checks, and input sanitization

**Technical Highlights:**

* CSS v4 Framework with design token system
* BEM methodology for component styling
* Modern dependency injection container
* Repository-Service pattern architecture
* AJAX-powered admin interface
* Comprehensive error logging and debugging tools

== Installation ==

1. Upload the `mobility-trailblazers` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'MT Award System' in the admin menu to configure the plugin
4. Run the database upgrade if prompted
5. Configure user roles and permissions as needed

== Frequently Asked Questions ==

= What WordPress version is required? =

The plugin requires WordPress 5.8 or higher and PHP 7.4 or higher.

= How do I import candidates? =

Use the Import feature in the admin panel or the WP-CLI command:
`wp mt import-candidates --excel=path/to/file.xlsx --photos=path/to/photos/`

= Can I customize the evaluation criteria? =

Yes, the evaluation system is flexible and can be customized through the admin interface.

= Is the plugin multisite compatible? =

The plugin is designed for single-site installations but can be adapted for multisite networks.

= How do I enable debug mode? =

Set `WP_DEBUG` to true in your wp-config.php file and check the Debug Center in the admin panel.

== Screenshots ==

1. Admin dashboard showing candidate management interface
2. Jury evaluation form with 5-criteria scoring system
3. Import interface for bulk candidate uploads
4. Assignment management for jury-candidate mappings
5. Debug center for monitoring and troubleshooting

== Changelog ==

= 2.5.34 =
* Repository restructuring for improved organization
* Consolidated test infrastructure
* Improved documentation structure
* WordPress.org compatibility enhancements

= 2.5.33 =
* Critical version alignment
* Security fixes for WP-CLI vulnerabilities
* Visual regression test setup
* Documentation improvements

= 2.5.32 =
* CSS v4 framework implementation
* BEM methodology adoption
* Performance optimizations
* German translation improvements

= 2.5.31 =
* Database migration system enhancements
* Import/export improvements
* Bug fixes and stability improvements

= 2.5.30 =
* Initial public release
* Core functionality implementation
* Basic award management features

== Upgrade Notice ==

= 2.5.34 =
Repository structure has been reorganized. Please clear caches after updating.

= 2.5.33 =
Critical security update. Please update immediately.

== Additional Information ==

**Support Resources:**

* Documentation: Available in the `/docs/` directory
* Debug Center: Admin → MT Award System → Debug Center
* Error Logs: Check `/wp-content/debug.log` when WP_DEBUG is enabled

**Development:**

* GitHub Repository: https://github.com/nicolasestrem/mobility-trailblazers
* Issue Tracking: https://github.com/nicolasestrem/mobility-trailblazers/issues

**Award Information:**

* Platform Launch: August 18, 2025
* Award Ceremony: October 30, 2025
* Website: https://mobilitytrailblazers.de/vote/

For more information and updates, visit the official website or contact the development team.