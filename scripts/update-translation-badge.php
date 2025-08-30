#!/usr/bin/env php
<?php
/**
 * Update Translation Badge in README
 * 
 * This script updates the translation status badge in README.md
 * based on the current translation percentage.
 * 
 * @package Mobility_Trailblazers
 * @version 1.0.0
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Configuration
define('PROJECT_ROOT', dirname(__DIR__));
define('README_FILE', PROJECT_ROOT . '/README.md');
define('LANGUAGES_DIR', PROJECT_ROOT . '/languages');

/**
 * Get translation stats
 */
function get_translation_stats() {
    $script = PROJECT_ROOT . '/scripts/german-translation-automation.php';
    
    if (!file_exists($script)) {
        return false;
    }
    
    // Run analysis
    exec("php " . escapeshellarg($script) . " analyze 2>&1", $output);
    
    $stats = [
        'percentage' => 0,
        'total' => 0,
        'translated' => 0,
        'untranslated' => 0
    ];
    
    foreach ($output as $line) {
        if (preg_match('/Translated:\s+(\d+)\s+\(([\d.]+)%\)/', $line, $matches)) {
            $stats['translated'] = (int)$matches[1];
            $stats['percentage'] = (float)$matches[2];
        } elseif (preg_match('/Total strings:\s+(\d+)/', $line, $matches)) {
            $stats['total'] = (int)$matches[1];
        } elseif (preg_match('/Untranslated:\s+(\d+)/', $line, $matches)) {
            $stats['untranslated'] = (int)$matches[1];
        }
    }
    
    return $stats;
}

/**
 * Generate badge URL
 */
function generate_badge_url($percentage) {
    // Determine color based on percentage
    if ($percentage >= 95) {
        $color = 'brightgreen';
    } elseif ($percentage >= 80) {
        $color = 'yellow';
    } elseif ($percentage >= 60) {
        $color = 'orange';
    } else {
        $color = 'red';
    }
    
    // Format percentage
    $label = 'German%20Translation';
    $message = $percentage . '%25';
    
    // Shields.io badge URL
    $badge_url = "https://img.shields.io/badge/{$label}-{$message}-{$color}";
    
    return $badge_url;
}

/**
 * Generate status badge URL
 */
function generate_status_badge_url($percentage) {
    if ($percentage >= 95) {
        $status = 'production%20ready';
        $color = 'success';
    } elseif ($percentage >= 80) {
        $status = 'in%20progress';
        $color = 'yellow';
    } else {
        $status = 'needs%20work';
        $color = 'critical';
    }
    
    $label = 'Translation%20Status';
    
    // Shields.io badge URL
    $badge_url = "https://img.shields.io/badge/{$label}-{$status}-{$color}";
    
    return $badge_url;
}

/**
 * Update README file
 */
function update_readme($stats) {
    if (!file_exists(README_FILE)) {
        echo "README.md not found\n";
        return false;
    }
    
    $readme = file_get_contents(README_FILE);
    $percentage = $stats['percentage'];
    
    // Generate badge URLs
    $translation_badge = generate_badge_url($percentage);
    $status_badge = generate_status_badge_url($percentage);
    
    // Badge markdown
    $badges = [
        'translation' => "![German Translation]($translation_badge)",
        'status' => "![Translation Status]($status_badge)",
        'ci' => "![Translation CI](https://github.com/nicolasestrem/mobility-trailblazers/workflows/Translation%20Validation/badge.svg)"
    ];
    
    // Look for existing badges section
    if (preg_match('/<!-- translation-badges-start -->.*<!-- translation-badges-end -->/s', $readme)) {
        // Replace existing badges
        $badge_section = <<<BADGES
<!-- translation-badges-start -->
{$badges['translation']}
{$badges['status']}
{$badges['ci']}
<!-- translation-badges-end -->
BADGES;
        
        $readme = preg_replace(
            '/<!-- translation-badges-start -->.*<!-- translation-badges-end -->/s',
            $badge_section,
            $readme
        );
    } else {
        // Add badges after the title
        if (preg_match('/^#\s+.+$/m', $readme, $matches, PREG_OFFSET_CAPTURE)) {
            $title_end = $matches[0][1] + strlen($matches[0][0]);
            
            $badge_section = "\n\n<!-- translation-badges-start -->\n";
            $badge_section .= "{$badges['translation']}\n";
            $badge_section .= "{$badges['status']}\n";
            $badge_section .= "{$badges['ci']}\n";
            $badge_section .= "<!-- translation-badges-end -->\n";
            
            $readme = substr($readme, 0, $title_end) . $badge_section . substr($readme, $title_end);
        }
    }
    
    // Update or add translation statistics section
    $stats_section = generate_stats_section($stats);
    
    if (preg_match('/<!-- translation-stats-start -->.*<!-- translation-stats-end -->/s', $readme)) {
        // Replace existing stats
        $readme = preg_replace(
            '/<!-- translation-stats-start -->.*<!-- translation-stats-end -->/s',
            $stats_section,
            $readme
        );
    } else {
        // Add stats section before the installation section or at the end
        if (preg_match('/##\s+Installation/i', $readme, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1];
            $readme = substr($readme, 0, $position) . $stats_section . "\n\n" . substr($readme, $position);
        } else {
            $readme .= "\n\n" . $stats_section;
        }
    }
    
    // Write updated README
    file_put_contents(README_FILE, $readme);
    
    echo "README.md updated successfully!\n";
    echo "Translation: {$percentage}%\n";
    echo "Badge URL: $translation_badge\n";
    
    return true;
}

/**
 * Generate statistics section
 */
function generate_stats_section($stats) {
    $percentage = $stats['percentage'];
    $total = $stats['total'];
    $translated = $stats['translated'];
    $untranslated = $stats['untranslated'];
    
    $status = ($percentage >= 95) ? '✅ Production Ready' : '⚠️ In Progress';
    $updated = date('Y-m-d H:i:s');
    
    $section = <<<SECTION
<!-- translation-stats-start -->
## 🌍 Translation Status

**German (de_DE)**: {$percentage}% Complete - {$status}

| Metric | Count | Status |
|--------|-------|--------|
| Total Strings | {$total} | - |
| Translated | {$translated} | ✅ |
| Untranslated | {$untranslated} | ⏳ |
| Required Coverage | 95% | {$percentage}% |

*Last updated: {$updated}*

### Translation Commands

```bash
# Check current status
npm run i18n:analyze

# Validate for deployment
npm run i18n:validate

# Extract untranslated strings
npm run i18n:extract

# Compile translations
npm run i18n:compile

# Install pre-commit hooks
npm run i18n:install-hooks
```
<!-- translation-stats-end -->
SECTION;
    
    return $section;
}

// Main execution
echo "Updating translation badges in README.md...\n";

$stats = get_translation_stats();

if (!$stats) {
    echo "Failed to get translation statistics\n";
    exit(1);
}

if (update_readme($stats)) {
    echo "Done!\n";
    exit(0);
} else {
    echo "Failed to update README\n";
    exit(1);
}