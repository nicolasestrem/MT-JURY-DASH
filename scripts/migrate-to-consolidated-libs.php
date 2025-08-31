<?php
/**
 * Migration Script for Consolidating to MTCore.js and MT_Base_Repository
 * 
 * This script helps migrate remaining modules to use the consolidated libraries.
 * Run with: php scripts/migrate-to-consolidated-libs.php [--dry-run]
 * 
 * @package MobilityTrailblazers
 * @since 4.2.0
 */

// Exit if accessed directly or not CLI
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

// Configuration
$dry_run = in_array('--dry-run', $argv);
$plugin_dir = dirname(dirname(__FILE__));

echo "=================================================================\n";
echo "Mobility Trailblazers - Library Consolidation Migration\n";
echo "=================================================================\n";
echo $dry_run ? "[DRY RUN MODE - No files will be modified]\n\n" : "[LIVE MODE - Files will be modified]\n\n";

// Track migration status
$migration_report = [
    'javascript' => [],
    'repositories' => [],
    'ajax' => [],
    'errors' => [],
    'backups' => []
];

/**
 * Backup a file before modification
 */
function backup_file($file_path) {
    global $dry_run, $migration_report;
    
    if ($dry_run) {
        return true;
    }
    
    $backup_dir = dirname($file_path) . '/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_path = $backup_dir . '/' . basename($file_path) . '.backup-' . date('Y-m-d-His');
    if (copy($file_path, $backup_path)) {
        $migration_report['backups'][] = $backup_path;
        return true;
    }
    return false;
}

/**
 * Migrate JavaScript files to use MTCore library
 */
function migrate_javascript_files() {
    global $plugin_dir, $dry_run, $migration_report;
    
    echo "Migrating JavaScript files to use MTCore library...\n";
    echo "-------------------------------------------------\n";
    
    $js_files_to_migrate = [
        'assets/js/mt-evaluations-admin.js',
        'assets/js/csv-import.js',
        'assets/js/candidate-import.js',
        'assets/js/debug-center.js'
    ];
    
    foreach ($js_files_to_migrate as $file) {
        $file_path = $plugin_dir . '/' . $file;
        
        if (!file_exists($file_path)) {
            echo "  ❌ File not found: $file\n";
            $migration_report['errors'][] = "File not found: $file";
            continue;
        }
        
        echo "  Processing: $file\n";
        
        // Read file content
        $content = file_get_contents($file_path);
        $original_content = $content;
        $changes = [];
        
        // Pattern replacements for MTCore migration
        $replacements = [
            // AJAX replacements
            '/\$\.ajax\s*\(\s*\{/' => 'MTCore.ajax.request({',
            '/ajaxurl/' => 'MTCore.ajax.getConfig().ajax_url',
            '/mt_[a-z_]+\.ajax_url/' => 'MTCore.ajax.getConfig().ajax_url',
            '/mt_[a-z_]+\.nonce/' => 'MTCore.ajax.getConfig().nonce',
            
            // Notification replacements
            '/alert\s*\(/' => 'MTCore.notify.show(',
            '/console\.error\s*\(([^)]+)\)/' => 'MTCore.notify.error($1)',
            
            // Loading state replacements
            '/\.addClass\s*\(\s*[\'"]loading[\'"]\s*\)/' => '.addClass(MTCore.ui.getLoadingClass())',
            '/\.removeClass\s*\(\s*[\'"]loading[\'"]\s*\)/' => '.removeClass(MTCore.ui.getLoadingClass())',
            
            // Escape HTML replacements
            '/escapeHtml\s*:\s*function[^}]+\}/' => '// Using MTCore.utils.escapeHtml instead',
            '/this\.escapeHtml/' => 'MTCore.utils.escapeHtml',
            '/self\.escapeHtml/' => 'MTCore.utils.escapeHtml'
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $count = 0;
            $content = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($count > 0) {
                $changes[] = "Replaced $count occurrences of pattern: $pattern";
            }
        }
        
        // Add MTCore dependency comment if not present
        if (!strpos($content, '@requires MTCore')) {
            $content = preg_replace(
                '/(\* @package[^\n]*\n)/',
                "$1 * @requires MTCore\n",
                $content
            );
            $changes[] = "Added @requires MTCore documentation";
        }
        
        // Check if file was modified
        if ($content !== $original_content) {
            if (!$dry_run) {
                backup_file($file_path);
                file_put_contents($file_path, $content);
            }
            
            echo "    ✅ Migrated successfully (" . count($changes) . " changes)\n";
            $migration_report['javascript'][] = [
                'file' => $file,
                'status' => 'migrated',
                'changes' => $changes
            ];
        } else {
            echo "    ℹ️ No changes needed\n";
            $migration_report['javascript'][] = [
                'file' => $file,
                'status' => 'no_changes'
            ];
        }
    }
    
    echo "\n";
}

/**
 * Migrate PHP repositories to extend MT_Base_Repository
 */
function migrate_php_repositories() {
    global $plugin_dir, $dry_run, $migration_report;
    
    echo "Migrating PHP repositories to extend MT_Base_Repository...\n";
    echo "----------------------------------------------------------\n";
    
    $repositories_to_migrate = [
        'includes/repositories/class-mt-assignment-repository.php',
        'includes/repositories/class-mt-audit-log-repository.php',
        'includes/repositories/class-mt-candidate-repository.php'
    ];
    
    foreach ($repositories_to_migrate as $file) {
        $file_path = $plugin_dir . '/' . $file;
        
        if (!file_exists($file_path)) {
            echo "  ❌ File not found: $file\n";
            $migration_report['errors'][] = "File not found: $file";
            continue;
        }
        
        echo "  Processing: $file\n";
        
        // Read file content
        $content = file_get_contents($file_path);
        $original_content = $content;
        $changes = [];
        
        // Check if already extends MT_Base_Repository
        if (strpos($content, 'extends MT_Base_Repository') !== false) {
            echo "    ℹ️ Already extends MT_Base_Repository\n";
            $migration_report['repositories'][] = [
                'file' => $file,
                'status' => 'already_migrated'
            ];
            continue;
        }
        
        // Extract class name and table suffix
        preg_match('/class\s+(\w+)/', $content, $class_matches);
        $class_name = $class_matches[1] ?? '';
        
        // Determine table suffix based on class name
        $table_suffix = '';
        if (strpos($class_name, 'Assignment') !== false) {
            $table_suffix = 'jury_assignments';
        } elseif (strpos($class_name, 'Audit_Log') !== false) {
            $table_suffix = 'audit_log';
        } elseif (strpos($class_name, 'Candidate') !== false) {
            $table_suffix = 'candidates';
        }
        
        // Pattern replacements for repository migration
        $replacements = [
            // Class declaration
            '/class\s+' . preg_quote($class_name) . '\s+implements\s+\w+/' => 
                "class $class_name extends MT_Base_Repository implements " . str_replace('_Repository', '_Repository_Interface', $class_name),
            
            // Remove duplicate properties that exist in base class
            '/private\s+\$table_name;[\r\n]+/' => '',
            '/protected\s+\$table_name;[\r\n]+/' => '',
            '/private\s+\$wpdb;[\r\n]+/' => '',
            '/protected\s+\$wpdb;[\r\n]+/' => '',
            
            // Remove constructor if it only sets table_name
            '/public\s+function\s+__construct\(\)\s*\{[^}]*\$this->table_name[^}]*\}/' => 
                "public function __construct() {\n        parent::__construct();\n        \$this->table_name = \$this->wpdb->prefix . '$table_suffix';\n    }",
            
            // Remove duplicate find() method if it's basic
            '/public\s+function\s+find\(\$id\)\s*\{[^}]*wpdb->get_row[^}]*\}/' => 
                '// find() method inherited from MT_Base_Repository'
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $count = 0;
            $content = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($count > 0) {
                $changes[] = "Applied pattern replacement: " . substr($pattern, 0, 50) . "...";
            }
        }
        
        // Add get_table_suffix method
        if (!strpos($content, 'get_table_suffix')) {
            $get_table_suffix_method = "\n    /**\n     * Get table suffix for this repository\n     * @return string\n     */\n    protected function get_table_suffix() {\n        return '$table_suffix';\n    }\n";
            
            // Insert before the last closing brace
            $content = preg_replace('/(\n\}[\s]*$)/', $get_table_suffix_method . '$1', $content);
            $changes[] = "Added get_table_suffix() method";
        }
        
        // Check if file was modified
        if ($content !== $original_content) {
            if (!$dry_run) {
                backup_file($file_path);
                file_put_contents($file_path, $content);
            }
            
            echo "    ✅ Migrated successfully (" . count($changes) . " changes)\n";
            $migration_report['repositories'][] = [
                'file' => $file,
                'status' => 'migrated',
                'changes' => $changes
            ];
        } else {
            echo "    ℹ️ No changes needed\n";
            $migration_report['repositories'][] = [
                'file' => $file,
                'status' => 'no_changes'
            ];
        }
    }
    
    echo "\n";
}

/**
 * Migrate AJAX handlers to use MT_Ajax_Helpers trait
 */
function migrate_ajax_handlers() {
    global $plugin_dir, $dry_run, $migration_report;
    
    echo "Migrating AJAX handlers to use MT_Ajax_Helpers trait...\n";
    echo "-------------------------------------------------------\n";
    
    $ajax_files_to_migrate = [
        'includes/ajax/class-mt-csv-import-ajax.php',
        'includes/ajax/class-mt-debug-ajax.php',
        'includes/ajax/class-mt-import-ajax.php'
    ];
    
    foreach ($ajax_files_to_migrate as $file) {
        $file_path = $plugin_dir . '/' . $file;
        
        if (!file_exists($file_path)) {
            echo "  ❌ File not found: $file\n";
            $migration_report['errors'][] = "File not found: $file";
            continue;
        }
        
        echo "  Processing: $file\n";
        
        // Read file content
        $content = file_get_contents($file_path);
        $original_content = $content;
        $changes = [];
        
        // Check if already uses trait
        if (strpos($content, 'use MT_Ajax_Helpers') !== false) {
            echo "    ℹ️ Already uses MT_Ajax_Helpers trait\n";
            $migration_report['ajax'][] = [
                'file' => $file,
                'status' => 'already_migrated'
            ];
            continue;
        }
        
        // Add use statement for trait
        if (!strpos($content, 'use MobilityTrailblazers\\Traits\\MT_Ajax_Helpers;')) {
            $content = preg_replace(
                '/(namespace[^;]+;[\r\n]+)/',
                "$1\nuse MobilityTrailblazers\\Traits\\MT_Ajax_Helpers;\n",
                $content
            );
            $changes[] = "Added use statement for MT_Ajax_Helpers trait";
        }
        
        // Add trait to class
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+MT_Base_Ajax\s*\{)/',
            "$1\n    use MT_Ajax_Helpers;\n",
            $content
        );
        $changes[] = "Added MT_Ajax_Helpers trait to class";
        
        // Replace common patterns with trait methods
        $replacements = [
            // Nonce verification
            '/if\s*\(\s*!\s*wp_verify_nonce[^)]+\)\s*\{[^}]*\$this->error[^}]*\}/' =>
                "if (!\$this->verify_ajax_request('mt_ajax_nonce')) {\n            return;\n        }",
            
            // Capability checks
            '/if\s*\(\s*!\s*current_user_can\([\'"]([^\'"]+)[\'"]\)\s*\)\s*\{[^}]*\$this->error[^}]*\}/' =>
                "if (!\$this->check_permission('$1')) {\n            return;\n        }",
            
            // File upload validation
            '/\$this->validate_file_upload/' => '$this->validate_ajax_file_upload',
            
            // Repository access
            '/global\s+\$mt_container;[\r\n]+\s*\$repository\s*=\s*\$mt_container->get/' =>
                '$repository = $this->get_repository_from_container'
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $count = 0;
            $content = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($count > 0) {
                $changes[] = "Replaced pattern: " . substr($pattern, 0, 50) . "...";
            }
        }
        
        // Check if file was modified
        if ($content !== $original_content) {
            if (!$dry_run) {
                backup_file($file_path);
                file_put_contents($file_path, $content);
            }
            
            echo "    ✅ Migrated successfully (" . count($changes) . " changes)\n";
            $migration_report['ajax'][] = [
                'file' => $file,
                'status' => 'migrated',
                'changes' => $changes
            ];
        } else {
            echo "    ℹ️ No changes needed\n";
            $migration_report['ajax'][] = [
                'file' => $file,
                'status' => 'no_changes'
            ];
        }
    }
    
    echo "\n";
}

/**
 * Generate migration report
 */
function generate_report() {
    global $migration_report, $plugin_dir, $dry_run;
    
    echo "=================================================================\n";
    echo "Migration Report\n";
    echo "=================================================================\n\n";
    
    // JavaScript files
    echo "JavaScript Files:\n";
    echo "-----------------\n";
    $js_migrated = 0;
    foreach ($migration_report['javascript'] as $item) {
        if ($item['status'] === 'migrated') {
            $js_migrated++;
            echo "  ✅ {$item['file']} - Migrated with " . count($item['changes']) . " changes\n";
        } else {
            echo "  ℹ️ {$item['file']} - {$item['status']}\n";
        }
    }
    echo "  Total: $js_migrated migrated\n\n";
    
    // PHP Repositories
    echo "PHP Repositories:\n";
    echo "-----------------\n";
    $repo_migrated = 0;
    foreach ($migration_report['repositories'] as $item) {
        if ($item['status'] === 'migrated') {
            $repo_migrated++;
            echo "  ✅ {$item['file']} - Migrated with " . count($item['changes']) . " changes\n";
        } else {
            echo "  ℹ️ {$item['file']} - {$item['status']}\n";
        }
    }
    echo "  Total: $repo_migrated migrated\n\n";
    
    // AJAX Handlers
    echo "AJAX Handlers:\n";
    echo "--------------\n";
    $ajax_migrated = 0;
    foreach ($migration_report['ajax'] as $item) {
        if ($item['status'] === 'migrated') {
            $ajax_migrated++;
            echo "  ✅ {$item['file']} - Migrated with " . count($item['changes']) . " changes\n";
        } else {
            echo "  ℹ️ {$item['file']} - {$item['status']}\n";
        }
    }
    echo "  Total: $ajax_migrated migrated\n\n";
    
    // Errors
    if (!empty($migration_report['errors'])) {
        echo "Errors:\n";
        echo "-------\n";
        foreach ($migration_report['errors'] as $error) {
            echo "  ❌ $error\n";
        }
        echo "\n";
    }
    
    // Backups
    if (!empty($migration_report['backups']) && !$dry_run) {
        echo "Backups Created:\n";
        echo "----------------\n";
        foreach ($migration_report['backups'] as $backup) {
            echo "  📁 $backup\n";
        }
        echo "\n";
    }
    
    // Save detailed report to file
    $report_file = $plugin_dir . '/doc/migration-report-' . date('Y-m-d-His') . '.json';
    if (!$dry_run) {
        file_put_contents($report_file, json_encode($migration_report, JSON_PRETTY_PRINT));
        echo "Detailed report saved to: $report_file\n\n";
    }
}

// Run migrations
migrate_javascript_files();
migrate_php_repositories();
migrate_ajax_handlers();
generate_report();

echo "=================================================================\n";
echo $dry_run ? "DRY RUN COMPLETED - Review changes above\n" : "MIGRATION COMPLETED\n";
echo "=================================================================\n\n";

if ($dry_run) {
    echo "To apply changes, run without --dry-run flag:\n";
    echo "  php scripts/migrate-to-consolidated-libs.php\n\n";
} else {
    echo "Next steps:\n";
    echo "  1. Review the changes in the modified files\n";
    echo "  2. Run the testing checklist (see MIGRATION-TESTING-CHECKLIST.md)\n";
    echo "  3. If issues arise, use the rollback script\n";
    echo "  4. Clear all caches: wp cache flush\n\n";
}