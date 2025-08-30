#!/usr/bin/env node

/**
 * Comprehensive CSS Analyzer
 * Enhanced analyzer for Mobility Trailblazers project with:
 * - !important declaration counting
 * - Specificity scoring 
 * - Duplicate selector detection
 * - Unused CSS identification
 * - Performance metrics
 */

const fs = require('fs');
const path = require('path');
const { performance } = require('perf_hooks');

class CSSAnalyzer {
    constructor() {
        this.cssDirectory = path.join(__dirname, '..', 'Plugin', 'assets', 'css');
        this.outputDirectory = path.join(__dirname, '..', 'docs');
        this.results = {
            files: [],
            summary: {
                totalFiles: 0,
                totalImportant: 0,
                totalSelectors: 0,
                duplicateSelectors: 0,
                avgSpecificity: 0,
                totalSize: 0,
                analysisTime: 0
            },
            duplicates: new Map(),
            specificityScores: [],
            unusedRules: []
        };
    }

    /**
     * Calculate CSS selector specificity
     * Returns [a, b, c] where a=IDs, b=classes/attributes, c=elements
     */
    calculateSpecificity(selector) {
        // Clean the selector
        const cleanSelector = selector.replace(/::?[a-zA-Z-]+/g, '').replace(/:[a-zA-Z-()]+/g, '');
        
        const ids = (cleanSelector.match(/#[a-zA-Z][\w-]*/g) || []).length;
        const classes = (cleanSelector.match(/\.[a-zA-Z][\w-]*/g) || []).length;
        const attributes = (cleanSelector.match(/\[[^\]]*\]/g) || []).length;
        const elements = (cleanSelector.match(/^[a-zA-Z][\w-]*|[\s>+~][a-zA-Z][\w-]*/g) || []).length;
        
        return [ids, classes + attributes, elements];
    }

    /**
     * Convert specificity array to numeric score for comparison
     */
    specificityToScore(spec) {
        return spec[0] * 10000 + spec[1] * 100 + spec[2];
    }

    /**
     * Parse CSS content and extract metrics
     */
    parseCSSFile(filePath) {
        const content = fs.readFileSync(filePath, 'utf8');
        const relativePath = path.relative(this.cssDirectory, filePath);
        const stats = fs.statSync(filePath);
        
        const metrics = {
            file: relativePath,
            size: Math.round(stats.size / 1024 * 100) / 100, // KB
            important: 0,
            selectors: [],
            rules: 0,
            mediaQueries: 0,
            deepNesting: 0,
            zIndex: 0,
            duplicateSelectors: 0,
            maxSpecificity: [0, 0, 0],
            avgSpecificity: 0,
            issues: []
        };

        // Count !important declarations
        const importantMatches = content.match(/!important/g);
        metrics.important = importantMatches ? importantMatches.length : 0;

        // Count rules (CSS blocks)
        const ruleMatches = content.match(/\{[^}]*\}/g);
        metrics.rules = ruleMatches ? ruleMatches.length : 0;

        // Count media queries
        const mediaMatches = content.match(/@media[^{]*\{/g);
        metrics.mediaQueries = mediaMatches ? mediaMatches.length : 0;

        // Count z-index usage
        const zIndexMatches = content.match(/z-index\s*:\s*[^;]+/g);
        metrics.zIndex = zIndexMatches ? zIndexMatches.length : 0;
        
        // Check for extremely high z-index values
        if (zIndexMatches) {
            zIndexMatches.forEach(match => {
                const value = parseInt(match.match(/\d+/));
                if (value > 9999) {
                    metrics.issues.push(`High z-index value: ${value}`);
                }
            });
        }

        // Extract selectors and calculate specificity
        const selectorRegex = /([^{}]+)\s*\{/g;
        let match;
        const specificityScores = [];
        
        while ((match = selectorRegex.exec(content)) !== null) {
            const selectorsGroup = match[1].trim();
            // Split multiple selectors (comma-separated)
            const selectors = selectorsGroup.split(',').map(s => s.trim());
            
            selectors.forEach(selector => {
                if (selector && !selector.includes('@')) {
                    metrics.selectors.push(selector);
                    
                    // Track duplicate selectors
                    const selectorKey = `${relativePath}:${selector}`;
                    if (this.results.duplicates.has(selector)) {
                        this.results.duplicates.get(selector).push(relativePath);
                        metrics.duplicateSelectors++;
                    } else {
                        this.results.duplicates.set(selector, [relativePath]);
                    }
                    
                    // Calculate specificity
                    const specificity = this.calculateSpecificity(selector);
                    const score = this.specificityToScore(specificity);
                    specificityScores.push(score);
                    
                    // Track max specificity
                    if (score > this.specificityToScore(metrics.maxSpecificity)) {
                        metrics.maxSpecificity = specificity;
                    }
                }
            });
        }

        // Calculate average specificity
        if (specificityScores.length > 0) {
            metrics.avgSpecificity = Math.round(
                specificityScores.reduce((a, b) => a + b, 0) / specificityScores.length
            );
        }

        // Count deep nesting (heuristic: selectors with many spaces/combinators)
        metrics.deepNesting = metrics.selectors.filter(selector => {
            const depth = (selector.match(/[\s>+~]/g) || []).length;
            return depth > 3;
        }).length;

        // Performance issues detection
        if (metrics.important > 50) {
            metrics.issues.push(`High !important usage: ${metrics.important} declarations`);
        }
        if (metrics.size > 100) {
            metrics.issues.push(`Large file size: ${metrics.size}KB`);
        }
        if (this.specificityToScore(metrics.maxSpecificity) > 300) {
            metrics.issues.push(`High specificity selectors detected`);
        }

        return metrics;
    }

    /**
     * Find CSS files recursively
     */
    findCSSFiles(dir) {
        const files = [];
        const items = fs.readdirSync(dir);
        
        for (const item of items) {
            const fullPath = path.join(dir, item);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                // Skip backup and minified directories
                if (!item.includes('backup') && !item.includes('min')) {
                    files.push(...this.findCSSFiles(fullPath));
                }
            } else if (item.endsWith('.css') && !item.endsWith('.min.css')) {
                files.push(fullPath);
            }
        }
        
        return files;
    }

    /**
     * Generate detailed analysis report
     */
    generateReport() {
        const timestamp = new Date().toISOString();
        const duplicateSelectors = Array.from(this.results.duplicates.entries())
            .filter(([selector, files]) => files.length > 1)
            .sort(([,a], [,b]) => b.length - a.length);

        const report = {
            timestamp,
            summary: this.results.summary,
            fileMetrics: this.results.files.sort((a, b) => b.important - a.important),
            duplicateSelectors: duplicateSelectors.slice(0, 20), // Top 20 duplicates
            riskAssessment: {
                critical: this.results.files.filter(f => f.important > 100),
                high: this.results.files.filter(f => f.important > 50 && f.important <= 100),
                medium: this.results.files.filter(f => f.important > 20 && f.important <= 50),
                low: this.results.files.filter(f => f.important > 0 && f.important <= 20)
            },
            recommendations: this.generateRecommendations()
        };

        return report;
    }

    /**
     * Generate actionable recommendations
     */
    generateRecommendations() {
        const recommendations = [];
        
        // !important issues
        const criticalFiles = this.results.files.filter(f => f.important > 100);
        if (criticalFiles.length > 0) {
            recommendations.push({
                priority: 'CRITICAL',
                category: '!important Declarations',
                action: `Refactor ${criticalFiles.length} files with >100 !important declarations`,
                files: criticalFiles.map(f => f.file),
                impact: 'High maintainability risk'
            });
        }

        // Duplicate selectors
        const duplicates = Array.from(this.results.duplicates.entries())
            .filter(([selector, files]) => files.length > 1);
        if (duplicates.length > 10) {
            recommendations.push({
                priority: 'HIGH',
                category: 'Code Duplication',
                action: `Consolidate ${duplicates.length} duplicate selectors`,
                impact: 'Reduce bundle size and improve maintainability'
            });
        }

        // Large files
        const largeFiles = this.results.files.filter(f => f.size > 50);
        if (largeFiles.length > 0) {
            recommendations.push({
                priority: 'MEDIUM',
                category: 'File Size',
                action: `Split or optimize ${largeFiles.length} large CSS files (>50KB)`,
                files: largeFiles.map(f => f.file),
                impact: 'Improve loading performance'
            });
        }

        // High specificity
        const highSpecFiles = this.results.files.filter(f => 
            this.specificityToScore(f.maxSpecificity) > 300
        );
        if (highSpecFiles.length > 0) {
            recommendations.push({
                priority: 'MEDIUM',
                category: 'Specificity',
                action: `Reduce specificity in ${highSpecFiles.length} files`,
                files: highSpecFiles.map(f => f.file),
                impact: 'Improve CSS maintainability'
            });
        }

        return recommendations;
    }

    /**
     * Export report in multiple formats
     */
    exportReport(report, format = 'json') {
        const outputPath = path.join(this.outputDirectory, `css-analysis-${Date.now()}.${format}`);
        
        if (format === 'json') {
            fs.writeFileSync(outputPath, JSON.stringify(report, null, 2));
        } else if (format === 'markdown') {
            const markdown = this.generateMarkdownReport(report);
            fs.writeFileSync(outputPath, markdown);
        }
        
        return outputPath;
    }

    /**
     * Generate markdown report
     */
    generateMarkdownReport(report) {
        return `# CSS Comprehensive Analysis Report

**Generated:** ${report.timestamp}
**Analysis Time:** ${report.summary.analysisTime}ms

## Executive Summary
| Metric | Value | Status |
|--------|-------|--------|
| Total Files | ${report.summary.totalFiles} | ✓ |
| Total !important | ${report.summary.totalImportant} | ${report.summary.totalImportant > 500 ? '⚠️' : '✓'} |
| Duplicate Selectors | ${report.duplicateSelectors.length} | ${report.duplicateSelectors.length > 50 ? '⚠️' : '✓'} |
| Total Size | ${report.summary.totalSize}KB | ${report.summary.totalSize > 1000 ? '⚠️' : '✓'} |
| Avg Specificity | ${report.summary.avgSpecificity} | ${report.summary.avgSpecificity > 200 ? '⚠️' : '✓'} |

## Risk Assessment
### Critical Files (>100 !important)
${report.riskAssessment.critical.map(f => `- ${f.file}: ${f.important} declarations`).join('\n')}

### High Risk Files (51-100 !important)
${report.riskAssessment.high.map(f => `- ${f.file}: ${f.important} declarations`).join('\n')}

## Top Duplicate Selectors
${report.duplicateSelectors.slice(0, 10).map(([selector, files]) => 
    `- \`${selector}\` (${files.length} files): ${files.join(', ')}`
).join('\n')}

## Recommendations
${report.recommendations.map(rec => 
    `### ${rec.priority}: ${rec.category}
**Action:** ${rec.action}
**Impact:** ${rec.impact}
${rec.files ? `**Files:** ${rec.files.join(', ')}` : ''}
`).join('\n')}

## File Analysis (Top 10 by !important count)
| File | !important | Size (KB) | Rules | Max Specificity | Issues |
|------|------------|-----------|-------|-----------------|--------|
${report.fileMetrics.slice(0, 10).map(f => 
    `| ${f.file} | ${f.important} | ${f.size} | ${f.rules} | ${f.maxSpecificity.join(',')} | ${f.issues.length} |`
).join('\n')}

---
*Generated by Mobility Trailblazers CSS Analyzer v2.0*
`;
    }

    /**
     * Main analysis function
     */
    async analyze() {
        console.log('🔍 Starting comprehensive CSS analysis...');
        const startTime = performance.now();
        
        try {
            // Find all CSS files
            const cssFiles = this.findCSSFiles(this.cssDirectory);
            console.log(`📁 Found ${cssFiles.length} CSS files`);
            
            // Analyze each file
            for (const file of cssFiles) {
                console.log(`📊 Analyzing: ${path.relative(this.cssDirectory, file)}`);
                const metrics = this.parseCSSFile(file);
                this.results.files.push(metrics);
            }
            
            // Calculate summary statistics
            this.results.summary.totalFiles = cssFiles.length;
            this.results.summary.totalImportant = this.results.files.reduce((sum, f) => sum + f.important, 0);
            this.results.summary.totalSelectors = this.results.files.reduce((sum, f) => sum + f.selectors.length, 0);
            this.results.summary.totalSize = Math.round(this.results.files.reduce((sum, f) => sum + f.size, 0) * 100) / 100;
            this.results.summary.avgSpecificity = Math.round(
                this.results.files.reduce((sum, f) => sum + f.avgSpecificity, 0) / cssFiles.length
            );
            this.results.summary.duplicateSelectors = Array.from(this.results.duplicates.entries())
                .filter(([selector, files]) => files.length > 1).length;
            
            const endTime = performance.now();
            this.results.summary.analysisTime = Math.round(endTime - startTime);
            
            // Generate report
            const report = this.generateReport();
            
            // Export in both formats
            const jsonPath = this.exportReport(report, 'json');
            const mdPath = this.exportReport(report, 'markdown');
            
            console.log('✅ Analysis complete!');
            console.log(`📊 JSON Report: ${jsonPath}`);
            console.log(`📝 Markdown Report: ${mdPath}`);
            console.log(`⚠️  Total !important declarations: ${this.results.summary.totalImportant}`);
            console.log(`🔄 Duplicate selectors: ${this.results.summary.duplicateSelectors}`);
            
            // Console summary
            if (this.results.summary.totalImportant > 1000) {
                console.log('🚨 CRITICAL: High !important usage detected');
            } else if (this.results.summary.totalImportant > 500) {
                console.log('⚠️  WARNING: Moderate !important usage');
            } else {
                console.log('✅ GOOD: Acceptable !important usage');
            }
            
            return report;
            
        } catch (error) {
            console.error('❌ Analysis failed:', error.message);
            throw error;
        }
    }
}

// CLI execution
if (require.main === module) {
    const analyzer = new CSSAnalyzer();
    analyzer.analyze().catch(console.error);
}

module.exports = CSSAnalyzer;