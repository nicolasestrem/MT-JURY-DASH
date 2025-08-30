#!/usr/bin/env node
/**
 * Critical CSS Bundling Script
 * Mobility Trailblazers WordPress Plugin
 * 
 * Strategy: Create context-specific CSS bundles to drastically reduce file count
 * Target: Consolidate 38 files into 4-5 critical bundles
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');

const PLUGIN_PATH = path.resolve(__dirname, '..');
const CSS_PATH = path.join(PLUGIN_PATH, 'Plugin', 'assets', 'css');
const OUTPUT_PATH = path.join(CSS_PATH, 'bundles');

// Ultra-aggressive clean-css options
const AGGRESSIVE_OPTIONS = {
    level: 2,
    compatibility: 'ie9',
    format: false,
    inline: ['all'],
    rebase: false,
    specialComments: 0,
    transform: {
        properties: ['margin', 'padding', 'background', 'border']
    }
};

console.log('=====================================');
console.log('🎯 CRITICAL CSS BUNDLING SCRIPT');
console.log('=====================================');
console.log('📍 Strategy: Consolidate into critical bundles');
console.log('');

// Define critical CSS bundles based on functionality
const CSS_BUNDLES = {
    'critical-admin.min.css': [
        'admin.css',
        'components/dashboard/mt-dashboard-widget.css',
        'components/stats/mt-jury-stats.css',
        'components/table/mt-assignments-table.css'
    ],
    'critical-jury.min.css': [
        'jury-dashboard.css',
        'mt-jury-dashboard-enhanced.css',
        'components/form/mt-evaluation-form.css',
        'mt-evaluation-forms.css',
        'mt-evaluation-fixes.css'
    ],
    'critical-candidates.min.css': [
        'enhanced-candidate-profile.css',
        'mt-candidate-cards-v3.css',
        'mt-candidate-grid.css',
        'components/card/mt-candidate-card.css'
    ],
    'critical-frontend.min.css': [
        'frontend-new.css',
        'mt-rankings-v2.css',
        'components/notification/mt-notification.css'
    ],
    'critical-framework.min.css': [
        'framework/mobility-trailblazers-framework-v4.css',
        'framework/mt-base.css',
        'framework/mt-reset.css',
        'mt-variables.css',
        'v4/mt-base.css',
        'v4/mt-components.css',
        'v4/mt-reset.css',
        'v4/mt-tokens.css'
    ]
};

/**
 * Get file size in KB
 */
function getFileSizeKB(filePath) {
    if (!fs.existsSync(filePath)) return 0;
    return Math.round((fs.statSync(filePath).size / 1024) * 100) / 100;
}

/**
 * Read and combine CSS files
 */
function combineCSS(files) {
    let combinedCSS = '';
    let totalOriginalSize = 0;
    
    files.forEach(file => {
        const filePath = path.join(CSS_PATH, file);
        if (fs.existsSync(filePath)) {
            const content = fs.readFileSync(filePath, 'utf8');
            combinedCSS += `\n/* === ${file} === */\n${content}\n`;
            totalOriginalSize += getFileSizeKB(filePath);
        } else {
            console.log(`   ⚠️  File not found: ${file}`);
        }
    });
    
    return { css: combinedCSS, originalSize: totalOriginalSize };
}

/**
 * Create critical CSS bundle
 */
function createBundle(bundleName, files) {
    console.log(`📦 Creating bundle: ${bundleName}`);
    
    const { css, originalSize } = combineCSS(files);
    
    if (!css.trim()) {
        console.log(`   ❌ No valid CSS found for ${bundleName}`);
        return { original: 0, minified: 0 };
    }
    
    // Apply aggressive minification
    const cleanCSS = new CleanCSS(AGGRESSIVE_OPTIONS);
    const result = cleanCSS.minify(css);
    
    if (result.errors && result.errors.length > 0) {
        console.log(`   ⚠️  Errors: ${result.errors.join(', ')}`);
    }
    
    // Write bundle
    const outputPath = path.join(OUTPUT_PATH, bundleName);
    fs.writeFileSync(outputPath, result.styles, 'utf8');
    
    const minifiedSize = getFileSizeKB(outputPath);
    const savings = Math.round(((originalSize - minifiedSize) / originalSize) * 100 * 100) / 100;
    
    console.log(`   📊 Files included: ${files.length}`);
    console.log(`   📦 Original total: ${originalSize} KB`);
    console.log(`   🎯 Minified bundle: ${minifiedSize} KB`);
    console.log(`   💰 Bundle savings: ${savings}%`);
    console.log('');
    
    return { original: originalSize, minified: minifiedSize };
}

/**
 * Main execution
 */
async function main() {
    // Ensure output directory exists
    if (!fs.existsSync(OUTPUT_PATH)) {
        fs.mkdirSync(OUTPUT_PATH, { recursive: true });
    }
    
    let totalOriginal = 0;
    let totalMinified = 0;
    
    // Create each bundle
    for (const [bundleName, files] of Object.entries(CSS_BUNDLES)) {
        const result = createBundle(bundleName, files);
        totalOriginal += result.original;
        totalMinified += result.minified;
    }
    
    // Create ultra-compressed single bundle (emergency fallback)
    console.log('🚀 Creating ULTRA bundle (all CSS in one file)...');
    const allFiles = Object.values(CSS_BUNDLES).flat();
    const ultraResult = createBundle('ultra-compressed.min.css', [...new Set(allFiles)]);
    
    // Summary
    console.log('=====================================');
    console.log('📊 BUNDLING SUMMARY');
    console.log('=====================================');
    console.log(`🎯 Bundles created: ${Object.keys(CSS_BUNDLES).length + 1}`);
    console.log(`📦 Total original size: ${totalOriginal} KB`);
    console.log(`🎯 Total bundled size: ${totalMinified} KB`);
    
    const bundleSavings = Math.round(((totalOriginal - totalMinified) / totalOriginal) * 100 * 100) / 100;
    console.log(`💰 Bundle savings: ${bundleSavings}%`);
    
    console.log('');
    console.log(`🚀 Ultra bundle: ${ultraResult.minified} KB`);
    
    // Check target achievement
    const targetSize = 50;
    if (ultraResult.minified <= targetSize) {
        console.log(`✅ ULTRA BUNDLE ACHIEVES TARGET! ${ultraResult.minified} KB ≤ ${targetSize} KB`);
    } else if (totalMinified <= targetSize) {
        console.log(`✅ CRITICAL BUNDLES ACHIEVE TARGET! ${totalMinified} KB ≤ ${targetSize} KB`);
    } else {
        console.log(`⚠️  Still above target. Best option: ${Math.min(totalMinified, ultraResult.minified)} KB`);
    }
    
    console.log('');
    console.log(`📁 Bundles saved to: ${OUTPUT_PATH}`);
    console.log('🔄 Next: Update WordPress to load critical bundles instead of individual files');
    console.log('');
    console.log('✅ Done!');
}

// Run the script
main().catch(error => {
    console.error('❌ Script failed:', error);
    process.exit(1);
});