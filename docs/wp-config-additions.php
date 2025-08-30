<?php
/**
 * CSS Feature Flags for Mobility Trailblazers Plugin
 * 
 * Add these lines to your wp-config.php file to control CSS loading behavior.
 * Place them before the line "/* That's all, stop editing! */"
 * 
 * @since Phase 1 CSS Stabilization
 * @date August 30, 2025
 */

// CSS Version Control
// Options: 'v3' (legacy), 'v4' (new), 'migration' (Phase 1 consolidated)
define('MT_CSS_VERSION', 'migration');

// Enable CSS debugging (shows which files are loaded in console)
define('MT_CSS_DEBUG', true);

// Use minified CSS files (set to false during development)
define('MT_USE_MINIFIED_CSS', false);

// Enable CSS performance monitoring
define('MT_CSS_PERFORMANCE_MONITOR', true);

// Maximum allowed z-index value (security feature)
define('MT_MAX_Z_INDEX', 9999);

// Enable visual regression testing mode
define('MT_CSS_TESTING_MODE', false);

// Rollback safety switch - set to true to force v3 CSS
define('MT_CSS_FORCE_LEGACY', false);