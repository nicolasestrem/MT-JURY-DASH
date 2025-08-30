#!/usr/bin/env node

/**
 * CSS Analyzer for Mobility Trailblazers
 * Comprehensive CSS analysis tool for tracking metrics and progress
 * 
 * Usage: node scripts/css-analyzer.js [options]
 * Options:
 *   --file <path>     Analyze specific file
 *   --dir <path>      Analyze directory (default: Plugin/assets/css)
 *   --output <type>   Output format: json, html, console (default: console)
 *   --verbose         Show detailed analysis
 */

const fs = require('fs');
const path = require('path');

class CSSAnalyzer {
    constructor() {
        this.results = {
            totalFiles: 0,
            totalLines: 0,
            totalSize: 0,
            importantCount: 0,
            selectors: [],
            duplicates: [],
            mediaQueries: [],
            colors: new Set(),
            zIndexValues: new Set(),
            specificity: [],
            files: []
        };
    }

    analyzeFile(filePath) {
        if (!fs.existsSync(filePath)) {
            console.error(`File not found: ${filePath}`);
            return null;
        }

        const content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const fileName = path.basename(filePath);
        
        const fileStats = {
            name: fileName,
            path: filePath,
            lines: lines.length,
            size: fs.statSync(filePath).size,
            importantCount: 0,
            selectors: [],
            mediaQueries: [],
            colors: new Set(),
            zIndexValues: new Set(),
            maxNesting: 0,
            bemCompliant: 0,
            idSelectors: 0
        };

        // Count !important declarations
        fileStats.importantCount = (content.match(/!important/gi) || []).length;

        // Extract selectors
        const selectorRegex = /^([^{]+)\s*{/gm;
        let match;
        while ((match = selectorRegex.exec(content)) !== null) {
            const selector = match[1].trim();
            if (selector && !selector.startsWith('@') && !selector.startsWith('/*')) {
                fileStats.selectors.push(selector);
                
                // Check for ID selectors
                if (selector.includes('#')) {
                    fileStats.idSelectors++;
                }
                
                // Check BEM compliance
                if (this.isBEMCompliant(selector)) {
                    fileStats.bemCompliant++;
                }
            }
        }

        // Extract media queries
        const mediaRegex = /@media[^{]+{/g;
        fileStats.mediaQueries = (content.match(mediaRegex) || []).map(mq => mq.trim());

        // Extract colors
        const colorRegex = /#[0-9a-fA-F]{3,8}|rgb\([^)]+\)|rgba\([^)]+\)|hsl\([^)]+\)|hsla\([^)]+\)/g;
        const colors = content.match(colorRegex) || [];
        colors.forEach(color => fileStats.colors.add(color.toLowerCase()));

        // Extract z-index values
        const zIndexRegex = /z-index:\s*([^;]+);/gi;
        while ((match = zIndexRegex.exec(content)) !== null) {
            const value = match[1].trim();
            if (value !== 'auto' && value !== 'inherit') {
                fileStats.zIndexValues.add(value);
            }
        }

        // Calculate max nesting depth
        fileStats.maxNesting = this.calculateMaxNesting(content);

        // Calculate specificity for each selector
        fileStats.selectors.forEach(selector => {
            const specificity = this.calculateSpecificity(selector);
            this.results.specificity.push({ selector, specificity, file: fileName });
        });

        // Update global results
        this.results.totalFiles++;
        this.results.totalLines += fileStats.lines;
        this.results.totalSize += fileStats.size;
        this.results.importantCount += fileStats.importantCount;
        this.results.files.push(fileStats);
        
        fileStats.colors.forEach(color => this.results.colors.add(color));
        fileStats.zIndexValues.forEach(value => this.results.zIndexValues.add(value));
        
        return fileStats;
    }

    analyzeDirectory(dirPath) {
        if (!fs.existsSync(dirPath)) {
            console.error(`Directory not found: ${dirPath}`);
            return;
        }

        const files = fs.readdirSync(dirPath);
        files.forEach(file => {
            if (file.endsWith('.css')) {
                this.analyzeFile(path.join(dirPath, file));
            }
        });

        // Find duplicate selectors
        this.findDuplicates();
        
        // Sort specificity scores
        this.results.specificity.sort((a, b) => b.specificity - a.specificity);
    }

    isBEMCompliant(selector) {
        // Simple BEM check: block__element--modifier pattern
        const bemRegex = /^\.?[a-z]([a-z0-9-]*)?(__[a-z][a-z0-9-]*)?(--[a-z][a-z0-9-]*)?$/i;
        return bemRegex.test(selector.split(/[\s>+~]/)[0]);
    }

    calculateSpecificity(selector) {
        let ids = (selector.match(/#[a-zA-Z][\w-]*/g) || []).length;
        let classes = (selector.match(/\.[a-zA-Z][\w-]*/g) || []).length;
        let attributes = (selector.match(/\[[^\]]+\]/g) || []).length;
        let pseudoClasses = (selector.match(/:[a-zA-Z-]+/g) || []).length;
        let elements = (selector.match(/^[a-zA-Z]+|[\s>+~][a-zA-Z]+/g) || []).length;
        
        // Specificity = (ids * 100) + (classes + attributes + pseudoClasses) * 10 + elements
        return (ids * 100) + ((classes + attributes + pseudoClasses) * 10) + elements;
    }

    calculateMaxNesting(content) {
        let maxDepth = 0;
        let currentDepth = 0;
        
        for (let i = 0; i < content.length; i++) {
            if (content[i] === '{') {
                currentDepth++;
                maxDepth = Math.max(maxDepth, currentDepth);
            } else if (content[i] === '}') {
                currentDepth--;
            }
        }
        
        return maxDepth;
    }

    findDuplicates() {
        const selectorMap = {};
        
        this.results.files.forEach(file => {
            file.selectors.forEach(selector => {
                if (!selectorMap[selector]) {
                    selectorMap[selector] = [];
                }
                selectorMap[selector].push(file.name);
            });
        });
        
        Object.entries(selectorMap).forEach(([selector, files]) => {
            if (files.length > 1) {
                this.results.duplicates.push({
                    selector,
                    files,
                    count: files.length
                });
            }
        });
        
        this.results.duplicates.sort((a, b) => b.count - a.count);
    }

    generateReport(outputType = 'console') {
        switch (outputType) {
            case 'json':
                return JSON.stringify(this.results, null, 2);
            
            case 'html':
                return this.generateHTMLReport();
            
            case 'console':
            default:
                return this.generateConsoleReport();
        }
    }

    generateConsoleReport() {
        console.log('\n' + '='.repeat(60));
        console.log('CSS ANALYSIS REPORT');
        console.log('='.repeat(60));
        
        console.log('\n📊 SUMMARY STATISTICS');
        console.log('-'.repeat(40));
        console.log(`Total Files: ${this.results.totalFiles}`);
        console.log(`Total Lines: ${this.results.totalLines.toLocaleString()}`);
        console.log(`Total Size: ${(this.results.totalSize / 1024).toFixed(2)} KB`);
        console.log(`!important Count: ${this.results.importantCount}`);
        console.log(`Unique Colors: ${this.results.colors.size}`);
        console.log(`Z-index Values: ${this.results.zIndexValues.size}`);
        console.log(`Duplicate Selectors: ${this.results.duplicates.length}`);
        
        console.log('\n🎯 TOP OFFENDERS (!important usage)');
        console.log('-'.repeat(40));
        const topOffenders = this.results.files
            .sort((a, b) => b.importantCount - a.importantCount)
            .slice(0, 5);
        
        topOffenders.forEach(file => {
            const percentage = ((file.importantCount / this.results.importantCount) * 100).toFixed(1);
            console.log(`${file.name}: ${file.importantCount} (${percentage}%)`);
        });
        
        console.log('\n⚠️ HIGH SPECIFICITY SELECTORS');
        console.log('-'.repeat(40));
        this.results.specificity.slice(0, 5).forEach(item => {
            console.log(`Score ${item.specificity}: ${item.selector}`);
            console.log(`  File: ${item.file}`);
        });
        
        console.log('\n🔄 DUPLICATE SELECTORS');
        console.log('-'.repeat(40));
        this.results.duplicates.slice(0, 5).forEach(dup => {
            console.log(`${dup.selector} (${dup.count} files)`);
            console.log(`  Files: ${dup.files.join(', ')}`);
        });
        
        console.log('\n📐 Z-INDEX VALUES');
        console.log('-'.repeat(40));
        const zIndexArray = Array.from(this.results.zIndexValues)
            .map(v => parseInt(v))
            .filter(v => !isNaN(v))
            .sort((a, b) => b - a);
        
        if (zIndexArray.length > 0) {
            console.log(`Highest: ${zIndexArray[0]}`);
            console.log(`Lowest: ${zIndexArray[zIndexArray.length - 1]}`);
            console.log(`Total unique values: ${zIndexArray.length}`);
            
            if (zIndexArray[0] > 9999) {
                console.log('⚠️ WARNING: Z-index exceeds 9999!');
            }
        }
        
        console.log('\n✅ BEM COMPLIANCE');
        console.log('-'.repeat(40));
        let totalSelectors = 0;
        let bemCompliant = 0;
        
        this.results.files.forEach(file => {
            totalSelectors += file.selectors.length;
            bemCompliant += file.bemCompliant;
        });
        
        const bemPercentage = totalSelectors > 0 
            ? ((bemCompliant / totalSelectors) * 100).toFixed(1)
            : 0;
        
        console.log(`BEM Compliant: ${bemCompliant}/${totalSelectors} (${bemPercentage}%)`);
        
        console.log('\n' + '='.repeat(60));
        console.log('END OF REPORT');
        console.log('='.repeat(60) + '\n');
    }

    generateHTMLReport() {
        const html = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Analysis Report</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #003C3D; }
        .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        .metric-value { font-size: 2em; font-weight: bold; color: #004C5F; }
        .metric-label { color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #003C3D; color: white; }
        .warning { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; }
    </style>
</head>
<body>
    <h1>CSS Analysis Report</h1>
    <p>Generated: ${new Date().toLocaleString()}</p>
    
    <div class="metrics">
        <div class="metric">
            <div class="metric-value">${this.results.totalFiles}</div>
            <div class="metric-label">Total Files</div>
        </div>
        <div class="metric">
            <div class="metric-value">${this.results.importantCount}</div>
            <div class="metric-label">!important Count</div>
        </div>
        <div class="metric">
            <div class="metric-value">${(this.results.totalSize / 1024).toFixed(1)}KB</div>
            <div class="metric-label">Total Size</div>
        </div>
        <div class="metric">
            <div class="metric-value">${this.results.duplicates.length}</div>
            <div class="metric-label">Duplicate Selectors</div>
        </div>
    </div>
    
    <h2>Files by !important Usage</h2>
    <table>
        <tr><th>File</th><th>!important Count</th><th>Percentage</th></tr>
        ${this.results.files
            .sort((a, b) => b.importantCount - a.importantCount)
            .slice(0, 10)
            .map(file => `
                <tr>
                    <td>${file.name}</td>
                    <td>${file.importantCount}</td>
                    <td>${((file.importantCount / this.results.importantCount) * 100).toFixed(1)}%</td>
                </tr>
            `).join('')}
    </table>
    
    <h2>High Specificity Selectors</h2>
    <table>
        <tr><th>Specificity Score</th><th>Selector</th><th>File</th></tr>
        ${this.results.specificity
            .slice(0, 10)
            .map(item => `
                <tr>
                    <td>${item.specificity}</td>
                    <td><code>${item.selector}</code></td>
                    <td>${item.file}</td>
                </tr>
            `).join('')}
    </table>
</body>
</html>`;
        return html;
    }
}

// Command line interface
if (require.main === module) {
    const analyzer = new CSSAnalyzer();
    const args = process.argv.slice(2);
    
    let targetPath = 'Plugin/assets/css';
    let outputType = 'console';
    let verbose = false;
    
    // Parse arguments
    for (let i = 0; i < args.length; i++) {
        switch (args[i]) {
            case '--file':
                targetPath = args[++i];
                break;
            case '--dir':
                targetPath = args[++i];
                break;
            case '--output':
                outputType = args[++i];
                break;
            case '--verbose':
                verbose = true;
                break;
            case '--help':
                console.log('Usage: node css-analyzer.js [options]');
                console.log('Options:');
                console.log('  --file <path>     Analyze specific file');
                console.log('  --dir <path>      Analyze directory');
                console.log('  --output <type>   Output format: json, html, console');
                console.log('  --verbose         Show detailed analysis');
                process.exit(0);
        }
    }
    
    // Run analysis
    if (fs.statSync(targetPath).isDirectory()) {
        analyzer.analyzeDirectory(targetPath);
    } else {
        analyzer.analyzeFile(targetPath);
    }
    
    // Generate report
    const report = analyzer.generateReport(outputType);
    
    if (outputType === 'json' || outputType === 'html') {
        console.log(report);
    }
}

module.exports = CSSAnalyzer;