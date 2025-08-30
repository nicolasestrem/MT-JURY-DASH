#!/usr/bin/env node
/**
 * Update WordPress CSS Enqueue Calls to Use Optimized URLs
 * Mobility Trailblazers WordPress Plugin
 * 
 * This script automatically updates wp_enqueue_style calls to use
 * the new get_optimized_css_url() method for better performance.
 */

const fs = require('fs');
const path = require('path');

const PLUGIN_PATH = path.resolve(__dirname, '..');
const PHP_FILES_TO_UPDATE = [
    'includes/core/class-mt-plugin.php',
    'includes/public/renderers/class-mt-shortcode-renderer.php',
    'templates/admin/assignments.php'
];

console.log('=====================================');
console.log('🔄 CSS ENQUEUE OPTIMIZATION SCRIPT');
console.log('=====================================');
console.log('📍 Updating wp_enqueue_style calls to use optimized CSS URLs');
console.log('');

/**
 * Update CSS enqueue calls in a PHP file
 */
function updateCSSEnqueueCalls(filePath) {
    const fullPath = path.join(PLUGIN_PATH, 'Plugin', filePath);
    
    if (!fs.existsSync(fullPath)) {
        console.log(`⚠️  File not found: ${filePath}`);
        return { updated: 0, file: filePath };
    }
    
    console.log(`📝 Processing: ${filePath}`);
    
    let content = fs.readFileSync(fullPath, 'utf8');
    let updateCount = 0;
    
    // Pattern to match CSS enqueue calls
    const cssEnqueuePattern = /wp_enqueue_style\(\s*'([^']+)',\s*MT_PLUGIN_URL\s*\.\s*'assets\/css\/([^']+\.css)'/g;
    
    // Replace with optimized version
    content = content.replace(cssEnqueuePattern, (match, handle, cssFile) => {
        updateCount++;
        console.log(`   ✅ Updated: ${cssFile} (handle: ${handle})`);
        return `wp_enqueue_style(\n            '${handle}',\n            $this->get_optimized_css_url('${cssFile}')`;
    });
    
    // Also handle cases without $this-> (for non-class contexts)
    const staticEnqueuePattern = /wp_enqueue_style\(\s*'([^']+)',\s*MT_PLUGIN_URL\s*\.\s*'assets\/css\/([^']+\.css)'/g;
    content = content.replace(staticEnqueuePattern, (match, handle, cssFile) => {
        // Only replace if we haven't already updated with $this->
        if (!match.includes('get_optimized_css_url')) {
            updateCount++;
            console.log(`   ✅ Updated (static): ${cssFile} (handle: ${handle})`);
            // For non-class contexts, we'll need to create a helper function
            return `wp_enqueue_style(\n            '${handle}',\n            mt_get_optimized_css_url('${cssFile}')`;
        }
        return match;
    });
    
    // Write updated content
    fs.writeFileSync(fullPath, content, 'utf8');
    
    console.log(`   📊 Total updates: ${updateCount}`);
    console.log('');
    
    return { updated: updateCount, file: filePath };
}

/**
 * Create global helper function for non-class contexts
 */
function createGlobalHelper() {
    const helperFile = path.join(PLUGIN_PATH, 'Plugin', 'includes', 'helpers', 'css-optimization.php');
    const helperDir = path.dirname(helperFile);
    
    // Ensure directory exists
    if (!fs.existsSync(helperDir)) {
        fs.mkdirSync(helperDir, { recursive: true });
    }
    
    const helperContent = `<?php
/**
 * CSS Optimization Helper Functions
 * 
 * Global helper functions for CSS optimization
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get optimized CSS URL (minified in production, regular in development)
 * 
 * @param string $css_file The relative CSS file path
 * @return string The optimized CSS URL
 */
function mt_get_optimized_css_url($css_file) {
    // Use minified version when not in debug mode
    $use_minified = !defined('WP_DEBUG') || !WP_DEBUG;
    
    if ($use_minified) {
        // Convert file.css to file.min.css
        $minified_file = str_replace('.css', '.min.css', $css_file);
        $minified_path = MT_PLUGIN_DIR . 'assets/css/' . $minified_file;
        
        // Only use minified version if it exists
        if (file_exists($minified_path)) {
            return MT_PLUGIN_URL . 'assets/css/' . $minified_file;
        }
    }
    
    // Fallback to original file
    return MT_PLUGIN_URL . 'assets/css/' . $css_file;
}

/**
 * Enqueue optimized CSS file
 * 
 * @param string $handle The CSS handle
 * @param string $css_file The relative CSS file path
 * @param array $deps Dependencies
 * @param string $version Version string
 * @param string $media Media type
 */
function mt_enqueue_optimized_css($handle, $css_file, $deps = [], $version = null, $media = 'all') {
    wp_enqueue_style(
        $handle,
        mt_get_optimized_css_url($css_file),
        $deps,
        $version ?: MT_VERSION,
        $media
    );
}
`;

    fs.writeFileSync(helperFile, helperContent, 'utf8');
    console.log(`📁 Created helper file: ${path.relative(PLUGIN_PATH, helperFile)}`);
    console.log('');
}

/**
 * Main execution
 */
async function main() {
    let totalUpdates = 0;
    const results = [];
    
    // Create global helper functions
    createGlobalHelper();
    
    // Update each PHP file
    for (const filePath of PHP_FILES_TO_UPDATE) {
        const result = updateCSSEnqueueCalls(filePath);
        results.push(result);
        totalUpdates += result.updated;
    }
    
    // Summary
    console.log('=====================================');
    console.log('📊 OPTIMIZATION SUMMARY');
    console.log('=====================================');
    console.log(`🎯 Files processed: ${results.length}`);
    console.log(`✅ Total CSS calls updated: ${totalUpdates}`);
    console.log('');
    
    results.forEach(result => {
        console.log(`📝 ${result.file}: ${result.updated} updates`);
    });
    
    if (totalUpdates > 0) {
        console.log('');
        console.log('🎉 SUCCESS: CSS enqueue calls optimized!');
        console.log('📍 Minified CSS files will be loaded automatically in production');
        console.log('🛠️  Original files will be used in WP_DEBUG mode');
    } else {
        console.log('');
        console.log('ℹ️  No updates needed - files already optimized');
    }
    
    console.log('');
    console.log('✅ Done!');
}

// Run the script
main().catch(error => {
    console.error('❌ Script failed:', error);
    process.exit(1);
});