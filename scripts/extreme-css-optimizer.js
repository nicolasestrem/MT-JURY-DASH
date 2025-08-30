#!/usr/bin/env node
/**
 * Extreme CSS Optimizer
 * Mobility Trailblazers WordPress Plugin
 * 
 * NUCLEAR OPTION: Apply extreme compression techniques
 * - Remove duplicate rules across files
 * - Eliminate unused CSS patterns
 * - Apply aggressive shorthand optimization
 * - Create production-ready micro-bundles
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');

const PLUGIN_PATH = path.resolve(__dirname, '..');
const CSS_PATH = path.join(PLUGIN_PATH, 'Plugin', 'assets', 'css');
const OUTPUT_PATH = path.join(CSS_PATH, 'production');

console.log('=====================================');
console.log('☢️  EXTREME CSS OPTIMIZER - NUCLEAR OPTION');
console.log('=====================================');
console.log('🎯 Target: <50KB total CSS bundle');
console.log('⚠️  Warning: This will create highly optimized, production-only CSS');
console.log('');

// Ultra-aggressive CleanCSS configuration
const NUCLEAR_OPTIONS = {
    level: {
        1: {
            optimizeBackground: true,
            optimizeBorderRadius: true,
            optimizeFilter: true,
            optimizeFont: true,
            optimizeFontWeight: true,
            optimizeOutline: true,
            removeNegativePaddings: true,
            removeQuotes: true,
            removeWhitespace: true,
            replaceMultipleZeros: true,
            replaceTimeUnits: true,
            replaceZeroUnits: true,
            roundingPrecision: 2,
            selectorsSortingMethod: 'alphabetical',
            specialComments: 0,
            tidyAtRules: true,
            tidyBlockScopes: true,
            tidySelectors: true
        },
        2: {
            mergeAdjacentRules: true,
            mergeIntoShorthands: true,
            mergeMedia: true,
            mergeNonAdjacentRules: true,
            mergeSemantically: true,
            overrideProperties: true,
            removeEmpty: true,
            reduceNonAdjacentRules: true,
            removeDuplicateFontRules: true,
            removeDuplicateMediaBlocks: true,
            removeDuplicateRules: true,
            removeUnusedAtRules: true,
            restructureRules: true,
            skipProperties: []
        }
    },
    compatibility: 'ie9',
    format: false,
    inline: ['all'],
    rebase: false
};

/**
 * Get file size in KB
 */
function getFileSizeKB(filePath) {
    if (!fs.existsSync(filePath)) return 0;
    return Math.round((fs.statSync(filePath).size / 1024) * 100) / 100;
}

/**
 * Read all CSS files and combine them
 */
function readAllCSS() {
    const cssFiles = [];
    const findCSSFiles = (dir) => {
        const files = fs.readdirSync(dir);
        files.forEach(file => {
            const filePath = path.join(dir, file);
            const stat = fs.statSync(filePath);
            if (stat.isDirectory()) {
                findCSSFiles(filePath);
            } else if (file.endsWith('.css') && !file.endsWith('.min.css') && !file.includes('bundle')) {
                cssFiles.push(filePath);
            }
        });
    };
    
    findCSSFiles(CSS_PATH);
    
    let combinedCSS = '';
    let totalSize = 0;
    
    cssFiles.forEach(filePath => {
        const relativePath = path.relative(CSS_PATH, filePath);
        const content = fs.readFileSync(filePath, 'utf8');
        combinedCSS += `\n/* ${relativePath} */\n${content}\n`;
        totalSize += getFileSizeKB(filePath);
    });
    
    return { css: combinedCSS, originalSize: totalSize, fileCount: cssFiles.length };
}

/**
 * Apply custom CSS optimizations beyond CleanCSS
 */
function applyCustomOptimizations(css) {
    console.log('🔬 Applying custom optimizations...');
    
    // Remove debug/development comments
    css = css.replace(/\/\*[\s\S]*?debug[\s\S]*?\*\//gi, '');
    css = css.replace(/\/\*[\s\S]*?TODO[\s\S]*?\*\//gi, '');
    css = css.replace(/\/\*[\s\S]*?FIXME[\s\S]*?\*\//gi, '');
    
    // Remove empty CSS rules more aggressively
    css = css.replace(/[^}]*\{\s*\}/g, '');
    
    // Compress color values more aggressively
    css = css.replace(/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3/g, '#$1$2$3');
    
    // Replace common long values with shorter equivalents
    css = css.replace(/transparent/g, '#0000');
    css = css.replace(/0px/g, '0');
    css = css.replace(/0em/g, '0');
    css = css.replace(/0rem/g, '0');
    css = css.replace(/0%/g, '0');
    
    // Compress whitespace patterns
    css = css.replace(/\s+/g, ' ');
    css = css.replace(/;\s*}/g, '}');
    css = css.replace(/\s*{\s*/g, '{');
    css = css.replace(/\s*}\s*/g, '}');
    css = css.replace(/\s*,\s*/g, ',');
    css = css.replace(/\s*:\s*/g, ':');
    css = css.replace(/\s*;\s*/g, ';');
    
    return css.trim();
}

/**
 * Create production CSS bundles
 */
function createProductionBundles() {
    console.log('📖 Reading all CSS files...');
    const { css, originalSize, fileCount } = readAllCSS();
    
    console.log(`📊 Processing ${fileCount} files (${originalSize} KB total)`);
    console.log('');
    
    // Create different optimization levels
    const optimizationLevels = [
        {
            name: 'production-ultra.min.css',
            description: 'Ultra-compressed single bundle',
            options: NUCLEAR_OPTIONS,
            customOptimization: true
        },
        {
            name: 'production-safe.min.css',
            description: 'Safer optimization level',
            options: {
                level: 2,
                compatibility: 'ie9',
                format: false,
                specialComments: 0
            },
            customOptimization: false
        }
    ];
    
    const results = [];
    
    optimizationLevels.forEach(level => {
        console.log(`⚡ Creating: ${level.name} (${level.description})`);
        
        let processedCSS = css;
        
        // Apply custom optimizations if enabled
        if (level.customOptimization) {
            processedCSS = applyCustomOptimizations(processedCSS);
        }
        
        // Apply CleanCSS
        const cleanCSS = new CleanCSS(level.options);
        const result = cleanCSS.minify(processedCSS);
        
        if (result.errors && result.errors.length > 0) {
            console.log(`   ⚠️  Errors: ${result.errors.slice(0, 3).join(', ')}${result.errors.length > 3 ? '...' : ''}`);
        }
        
        // Write optimized CSS
        const outputPath = path.join(OUTPUT_PATH, level.name);
        fs.writeFileSync(outputPath, result.styles, 'utf8');
        
        const finalSize = getFileSizeKB(outputPath);
        const savings = Math.round(((originalSize - finalSize) / originalSize) * 100 * 100) / 100;
        
        console.log(`   📦 Original: ${originalSize} KB`);
        console.log(`   🎯 Optimized: ${finalSize} KB`);
        console.log(`   💰 Savings: ${savings}%`);
        
        const targetAchieved = finalSize <= 50;
        if (targetAchieved) {
            console.log(`   ✅ TARGET ACHIEVED!`);
        }
        
        results.push({
            name: level.name,
            description: level.description,
            originalSize,
            finalSize,
            savings,
            targetAchieved
        });
        
        console.log('');
    });
    
    return results;
}

/**
 * Main execution
 */
async function main() {
    // Ensure output directory exists
    if (!fs.existsSync(OUTPUT_PATH)) {
        fs.mkdirSync(OUTPUT_PATH, { recursive: true });
    }
    
    const results = createProductionBundles();
    
    // Summary
    console.log('=====================================');
    console.log('📊 EXTREME OPTIMIZATION SUMMARY');
    console.log('=====================================');
    
    results.forEach(result => {
        console.log(`🎯 ${result.name}:`);
        console.log(`   📝 ${result.description}`);
        console.log(`   📦 Size: ${result.finalSize} KB (${result.savings}% reduction)`);
        console.log(`   ${result.targetAchieved ? '✅' : '❌'} Target: ${result.targetAchieved ? 'ACHIEVED' : 'NOT ACHIEVED'}`);
        console.log('');
    });
    
    // Find best result
    const bestResult = results.reduce((best, current) => 
        current.finalSize < best.finalSize ? current : best
    );
    
    console.log(`🏆 BEST RESULT: ${bestResult.name}`);
    console.log(`📦 Final size: ${bestResult.finalSize} KB`);
    console.log(`💰 Total reduction: ${bestResult.savings}%`);
    
    if (bestResult.targetAchieved) {
        console.log('🎉 SUCCESS: <50KB target achieved!');
    } else {
        console.log(`⚠️  Still ${Math.round((bestResult.finalSize - 50) * 100) / 100} KB over target`);
        console.log('💡 Consider removing non-essential CSS features manually');
    }
    
    console.log('');
    console.log(`📁 Production files saved to: ${OUTPUT_PATH}`);
    console.log('');
    console.log('✅ Done!');
}

// Run the script
main().catch(error => {
    console.error('❌ Script failed:', error);
    process.exit(1);
});