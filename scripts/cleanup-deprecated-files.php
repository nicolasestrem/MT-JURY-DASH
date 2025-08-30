#!/usr/bin/env php
<?php
/**
 * Cleanup Deprecated Files Script
 * 
 * This script removes backup directories, deprecated files, and test results
 * from the Mobility Trailblazers plugin.
 * 
 * Usage: php scripts/cleanup-deprecated-files.php [--dry-run] [--verbose]
 * 
 * @package MobilityTrailblazers
 * @version 1.0.0
 */

// Script configuration
$config = [
    'dry_run' => in_array('--dry-run', $argv),
    'verbose' => in_array('--verbose', $argv),
    'base_path' => dirname(__DIR__)
];

// Files and directories to remove
$cleanup_targets = [
    // Backup directories
    'backups' => [
        'path' => '/backups',
        'type' => 'directory',
        'description' => 'All backup files and directories',
        'size_estimate' => '4.0 MB'
    ],
    '.internal/backups' => [
        'path' => '/.internal/backups',
        'type' => 'directory',
        'description' => 'Internal backup directory (empty)',
        'size_estimate' => '0 KB'
    ],
    
    // Test results and reports  
    'test-results-no-auth' => [
        'path' => '/doc/test-results-no-auth',
        'type' => 'directory',
        'description' => 'Test results without authentication',
        'size_estimate' => '7.8 MB'
    ],
    'playwright-report-no-auth' => [
        'path' => '/doc/playwright-report-no-auth',
        'type' => 'directory',
        'description' => 'Playwright report without authentication',
        'size_estimate' => 'Unknown'
    ],
    'test-results-staging' => [
        'path' => '/doc/test-results-staging',
        'type' => 'directory',
        'description' => 'Staging test results',
        'size_estimate' => 'Unknown'
    ],
    'test-results-production' => [
        'path' => '/doc/test-results-production',
        'type' => 'directory',
        'description' => 'Production test results',
        'size_estimate' => 'Unknown'
    ],
    'playwright-report-staging' => [
        'path' => '/doc/playwright-report-staging',
        'type' => 'directory',
        'description' => 'Staging playwright reports',
        'size_estimate' => 'Unknown'
    ],
    'playwright-report-production' => [
        'path' => '/doc/playwright-report-production',
        'type' => 'directory',
        'description' => 'Production playwright reports',
        'size_estimate' => 'Unknown'
    ],
    
    // Legacy directories (if they exist)
    'exemple-old-css' => [
        'path' => '/Exemple old css',
        'type' => 'directory',
        'description' => 'Example old CSS directory',
        'size_estimate' => 'Unknown'
    ],
    'css-backup' => [
        'path' => '/css backup',
        'type' => 'directory',
        'description' => 'CSS backup directory',
        'size_estimate' => 'Unknown'
    ]
];

// Helper functions
function log_message($message, $type = 'INFO') {
    global $config;
    $prefix = sprintf('[%s] %s: ', date('Y-m-d H:i:s'), $type);
    echo $prefix . $message . PHP_EOL;
}

function verbose_log($message) {
    global $config;
    if ($config['verbose']) {
        log_message($message, 'DEBUG');
    }
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function get_directory_size($path) {
    $size = 0;
    if (!is_dir($path)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}

function delete_directory($path) {
    if (!is_dir($path)) {
        return false;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    
    return rmdir($path);
}

// Main execution
log_message('Starting Mobility Trailblazers cleanup script');
log_message('Base path: ' . $config['base_path']);

if ($config['dry_run']) {
    log_message('Running in DRY RUN mode - no files will be deleted', 'WARNING');
}

$total_size = 0;
$removed_count = 0;
$failed_count = 0;

// Process each cleanup target
foreach ($cleanup_targets as $key => $target) {
    $full_path = $config['base_path'] . $target['path'];
    
    verbose_log("Checking: $full_path");
    
    if ($target['type'] === 'directory') {
        if (is_dir($full_path)) {
            $size = get_directory_size($full_path);
            $total_size += $size;
            
            log_message(sprintf(
                'Found: %s (%s) - %s',
                $target['path'],
                format_bytes($size),
                $target['description']
            ));
            
            if (!$config['dry_run']) {
                if (delete_directory($full_path)) {
                    log_message("Removed: $target[path]", 'SUCCESS');
                    $removed_count++;
                } else {
                    log_message("Failed to remove: $target[path]", 'ERROR');
                    $failed_count++;
                }
            } else {
                log_message("Would remove: $target[path]", 'DRY-RUN');
                $removed_count++;
            }
        } else {
            verbose_log("Not found: $target[path]");
        }
    } elseif ($target['type'] === 'file') {
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            $total_size += $size;
            
            log_message(sprintf(
                'Found: %s (%s) - %s',
                $target['path'],
                format_bytes($size),
                $target['description']
            ));
            
            if (!$config['dry_run']) {
                if (unlink($full_path)) {
                    log_message("Removed: $target[path]", 'SUCCESS');
                    $removed_count++;
                } else {
                    log_message("Failed to remove: $target[path]", 'ERROR');
                    $failed_count++;
                }
            } else {
                log_message("Would remove: $target[path]", 'DRY-RUN');
                $removed_count++;
            }
        } else {
            verbose_log("Not found: $target[path]");
        }
    }
}

// Summary
log_message('=' . str_repeat('=', 50));
log_message('Cleanup Summary:');
log_message("Total size to be freed: " . format_bytes($total_size));
log_message("Items to remove: $removed_count");

if ($failed_count > 0) {
    log_message("Failed operations: $failed_count", 'WARNING');
}

if ($config['dry_run']) {
    log_message('This was a DRY RUN - no files were actually deleted', 'WARNING');
    log_message('Run without --dry-run flag to perform actual cleanup');
} else {
    log_message('Cleanup completed successfully!', 'SUCCESS');
}

// Additional recommendations
log_message('=' . str_repeat('=', 50));
log_message('Additional recommendations:');
log_message('1. Run "git gc" to clean up git objects');
log_message('2. Clear WordPress cache: wp cache flush');
log_message('3. Update .gitignore to prevent future accumulation');
log_message('4. Consider implementing automated cleanup in CI/CD pipeline');