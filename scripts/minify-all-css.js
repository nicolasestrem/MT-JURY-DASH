#!/usr/bin/env node
/**
 * Aggressive CSS Minification Script
 * Mobility Trailblazers WordPress Plugin
 * 
 * Target: Reduce 399KB CSS bundle to <50KB (87% reduction)
 * Uses clean-css library directly with maximum compression
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');

const PLUGIN_PATH = path.resolve(__dirname, '..');
const CSS_PATH = path.join(PLUGIN_PATH, 'Plugin', 'assets', 'css');

// Aggressive clean-css options for maximum compression
const CLEAN_CSS_OPTIONS = {
    level: 2, // Maximum optimization level
    compatibility: 'ie9', // IE9+ compatibility
    format: false, // No formatting, maximum compression
    inline: ['all'], // Inline all imports
    rebase: false, // Don't rebase URLs
    specialComments: 0 // Remove all comments
};

console.log('=====================================');
console.log('🚀 AGGRESSIVE CSS MINIFICATION SCRIPT');
console.log('=====================================');
console.log(`📍 Target: Reduce 399KB → <50KB (87% reduction)`);
console.log('');

/**
 * Recursively find all CSS files
 */
function findAllCSSFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);
    
    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        
        if (stat.isDirectory()) {
            findAllCSSFiles(filePath, fileList);
        } else if (file.endsWith('.css') && !file.endsWith('.min.css')) {
            fileList.push(filePath);
        }
    });
    
    return fileList;
}

/**
 * Get file size in KB
 */
function getFileSizeKB(filePath) {
    if (!fs.existsSync(filePath)) return 0;
    return Math.round((fs.statSync(filePath).size / 1024) * 100) / 100;
}

/**
 * Minify a single CSS file using clean-css library directly
 */
function minifyCSS(inputPath) {
    const outputPath = inputPath.replace('.css', '.min.css');
    const relativePath = path.relative(CSS_PATH, inputPath);
    
    console.log(`⚡ Processing: ${relativePath}`);
    
    try {
        const originalSize = getFileSizeKB(inputPath);
        const cssContent = fs.readFileSync(inputPath, 'utf8');
        
        // Use CleanCSS library directly for aggressive minification
        const cleanCSS = new CleanCSS(CLEAN_CSS_OPTIONS);
        const result = cleanCSS.minify(cssContent);
        
        if (result.errors && result.errors.length > 0) {
            console.error(`   ⚠️  Warnings: ${result.errors.join(', ')}`);
        }
        
        // Write minified CSS
        fs.writeFileSync(outputPath, result.styles, 'utf8');
        
        const minifiedSize = getFileSizeKB(outputPath);
        const savings = Math.round(((originalSize - minifiedSize) / originalSize) * 100 * 100) / 100;
        
        console.log(`   📦 Original: ${originalSize} KB`);
        console.log(`   🎯 Minified: ${minifiedSize} KB`);
        console.log(`   💰 Savings: ${savings}%`);
        console.log('');
        
        return {
            original: originalSize,
            minified: minifiedSize,
            savings: savings,
            path: relativePath
        };
        
    } catch (error) {
        console.error(`   ❌ ERROR: ${error.message}`);
        console.log('');
        return {
            original: 0,
            minified: 0,
            savings: 0,
            path: relativePath,
            error: error.message
        };
    }
}

/**
 * Main execution
 */
async function main() {
    // Check if clean-css is available
    try {
        require.resolve('clean-css');
    } catch (error) {
        console.error('❌ clean-css not found. Please run: npm install');
        process.exit(1);
    }
    
    // Find all CSS files
    const cssFiles = findAllCSSFiles(CSS_PATH);
    console.log(`📋 Found ${cssFiles.length} CSS files to process`);
    console.log('');
    
    // Process each file
    const results = [];
    let totalOriginal = 0;
    let totalMinified = 0;
    
    for (const cssFile of cssFiles) {
        const result = minifyCSS(cssFile);
        results.push(result);
        totalOriginal += result.original;
        totalMinified += result.minified;
    }
    
    // Summary
    console.log('=====================================');
    console.log('📊 MINIFICATION SUMMARY');
    console.log('=====================================');
    console.log(`🎯 Files processed: ${results.length}`);
    console.log(`📦 Total original size: ${totalOriginal} KB`);
    console.log(`🎯 Total minified size: ${totalMinified} KB`);
    
    const totalSavings = Math.round(((totalOriginal - totalMinified) / totalOriginal) * 100 * 100) / 100;
    console.log(`💰 Total savings: ${totalSavings}%`);
    console.log(`🚀 Size reduction: ${Math.round((totalOriginal - totalMinified) * 100) / 100} KB`);
    
    // Check if we met the target
    const targetSize = 50;
    if (totalMinified <= targetSize) {
        console.log(`✅ TARGET ACHIEVED! Bundle size: ${totalMinified} KB (≤ ${targetSize} KB)`);
    } else {
        console.log(`⚠️  Still above target: ${totalMinified} KB > ${targetSize} KB`);
        console.log(`🎯 Need additional ${Math.round((totalMinified - targetSize) * 100) / 100} KB reduction`);
    }
    
    console.log('');
    console.log('📁 Minified files saved with .min.css extension');
    console.log('🔄 Next: Update WordPress enqueue calls to load .min.css versions');
    console.log('');
    console.log('✅ Done!');
    
    // Save detailed report
    const reportPath = path.join(__dirname, 'css-minification-report.json');
    const report = {
        timestamp: new Date().toISOString(),
        totalFiles: results.length,
        totalOriginalSize: totalOriginal,
        totalMinifiedSize: totalMinified,
        totalSavings: totalSavings,
        targetAchieved: totalMinified <= targetSize,
        files: results
    };
    
    fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
    console.log(`📄 Detailed report saved: ${reportPath}`);
}

// Run the script
main().catch(error => {
    console.error('❌ Script failed:', error);
    process.exit(1);
});