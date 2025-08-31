#!/usr/bin/env php
<?php
/**
 * Translation Deployment Validator
 * 
 * This script validates translation readiness before deployment.
 * It blocks deployment if translations are below the required threshold.
 * 
 * Exit codes:
 *   0 - Validation passed, deployment can proceed
 *   1 - Validation failed, deployment blocked
 *   2 - Script error
 * 
 * @package Mobility_Trailblazers
 * @version 1.0.0
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Configuration
define('MT_MIN_TRANSLATION_PERCENTAGE', 95); // Required minimum
define('MT_WARN_TRANSLATION_PERCENTAGE', 98); // Warning threshold
define('MT_PROJECT_ROOT', dirname(__DIR__));
define('MT_LANGUAGES_DIR', MT_PROJECT_ROOT . '/languages');

// ANSI color codes
class Colors {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const PURPLE = "\033[0;35m";
    const CYAN = "\033[0;36m";
    const WHITE = "\033[1;37m";
    const RESET = "\033[0m";
    
    public static function colorize($text, $color) {
        return $color . $text . self::RESET;
    }
}

/**
 * Translation Deployment Validator Class
 */
class TranslationDeploymentValidator {
    
    private $errors = [];
    private $warnings = [];
    private $stats = [];
    private $verbose = false;
    private $force = false;
    
    /**
     * Constructor
     */
    public function __construct($verbose = false, $force = false) {
        $this->verbose = $verbose;
        $this->force = $force;
    }
    
    /**
     * Run validation
     */
    public function validate() {
        $this->printHeader();
        
        // Step 1: Check files exist
        if (!$this->checkRequiredFiles()) {
            return $this->fail("Required translation files are missing");
        }
        
        // Step 2: Validate POT file
        if (!$this->validatePotFile()) {
            return $this->fail("POT file validation failed");
        }
        
        // Step 3: Validate PO file
        if (!$this->validatePoFile()) {
            return $this->fail("PO file validation failed");
        }
        
        // Step 4: Check MO file
        if (!$this->checkMoFile()) {
            return $this->fail("MO file validation failed");
        }
        
        // Step 5: Analyze translation coverage
        if (!$this->analyzeTranslationCoverage()) {
            return $this->fail("Translation coverage below required threshold");
        }
        
        // Step 6: Validate translation quality
        if (!$this->validateTranslationQuality()) {
            return $this->fail("Translation quality issues detected");
        }
        
        // Step 7: Check for critical translations
        if (!$this->checkCriticalTranslations()) {
            return $this->fail("Critical translations are missing");
        }
        
        // Step 8: Performance check
        if (!$this->checkPerformance()) {
            return $this->fail("Translation files too large for optimal performance");
        }
        
        // Display results
        $this->displayResults();
        
        // Determine final status
        if (!empty($this->errors)) {
            return $this->fail("Deployment blocked due to translation issues");
        }
        
        if (!empty($this->warnings) && !$this->force) {
            $this->printWarning("\nWarnings detected. Deployment allowed but review recommended.");
        }
        
        return $this->success();
    }
    
    /**
     * Print header
     */
    private function printHeader() {
        echo Colors::colorize(str_repeat('=', 60), Colors::BLUE) . "\n";
        echo Colors::colorize("MOBILITY TRAILBLAZERS - TRANSLATION DEPLOYMENT VALIDATOR", Colors::BLUE) . "\n";
        echo Colors::colorize(str_repeat('=', 60), Colors::BLUE) . "\n\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Required Translation: " . MT_MIN_TRANSLATION_PERCENTAGE . "%\n";
        echo "Mode: " . ($this->force ? "Force" : "Standard") . "\n\n";
    }
    
    /**
     * Check required files exist
     */
    private function checkRequiredFiles() {
        $this->printStep("Checking required files");
        
        $required_files = [
            'mobility-trailblazers.pot' => 'POT template file',
            'mobility-trailblazers-de_DE.po' => 'German PO file',
            'mobility-trailblazers-de_DE.mo' => 'German MO file'
        ];
        
        $all_exist = true;
        
        foreach ($required_files as $file => $description) {
            $path = MT_LANGUAGES_DIR . '/' . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                $this->printSuccess("✓ $description exists (" . $this->formatBytes($size) . ")");
            } else {
                $this->printError("✗ $description missing");
                $this->errors[] = "$description is missing";
                $all_exist = false;
            }
        }
        
        return $all_exist;
    }
    
    /**
     * Validate POT file
     */
    private function validatePotFile() {
        $this->printStep("Validating POT file");
        
        $pot_file = MT_LANGUAGES_DIR . '/mobility-trailblazers.pot';
        
        // Check if POT is up to date
        $pot_mtime = filemtime($pot_file);
        $php_files_updated = false;
        
        // Check PHP files modification times
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(MT_PROJECT_ROOT)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Skip vendor and test directories
                if (strpos($file->getPathname(), '/vendor/') !== false ||
                    strpos($file->getPathname(), '/tests/') !== false) {
                    continue;
                }
                
                if (filemtime($file->getPathname()) > $pot_mtime) {
                    $php_files_updated = true;
                    break;
                }
            }
        }
        
        if ($php_files_updated) {
            $this->printWarning("⚠ POT file may be outdated (PHP files modified after POT generation)");
            $this->warnings[] = "POT file may need regeneration";
        } else {
            $this->printSuccess("✓ POT file appears up to date");
        }
        
        // Parse POT file
        $pot_content = file_get_contents($pot_file);
        $pot_strings = $this->parseGettextFile($pot_content);
        
        $this->stats['pot_strings'] = count($pot_strings);
        $this->printInfo("Found {$this->stats['pot_strings']} translatable strings");
        
        return true;
    }
    
    /**
     * Validate PO file
     */
    private function validatePoFile() {
        $this->printStep("Validating PO file");
        
        $po_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.po';
        
        // Check syntax using msgfmt if available
        if ($this->commandExists('msgfmt')) {
            exec("msgfmt -c -v -o /dev/null " . escapeshellarg($po_file) . " 2>&1", $output, $return_code);
            
            if ($return_code === 0) {
                $this->printSuccess("✓ PO file syntax is valid");
            } else {
                $this->printError("✗ PO file has syntax errors:");
                foreach ($output as $line) {
                    $this->printError("  " . $line);
                }
                $this->errors[] = "PO file syntax validation failed";
                return false;
            }
        } else {
            $this->printWarning("⚠ msgfmt not available, using basic validation");
        }
        
        // Parse PO file
        $po_content = file_get_contents($po_file);
        $po_strings = $this->parseGettextFile($po_content);
        
        $this->stats['po_strings'] = count($po_strings);
        $this->stats['translated'] = 0;
        $this->stats['fuzzy'] = 0;
        $this->stats['untranslated'] = 0;
        
        foreach ($po_strings as $entry) {
            if (!empty($entry['msgstr'])) {
                if (in_array('fuzzy', $entry['flags'])) {
                    $this->stats['fuzzy']++;
                } else {
                    $this->stats['translated']++;
                }
            } else {
                $this->stats['untranslated']++;
            }
        }
        
        $this->printInfo("Translated: {$this->stats['translated']}");
        $this->printInfo("Untranslated: {$this->stats['untranslated']}");
        $this->printInfo("Fuzzy: {$this->stats['fuzzy']}");
        
        return true;
    }
    
    /**
     * Check MO file
     */
    private function checkMoFile() {
        $this->printStep("Checking MO file");
        
        $po_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.po';
        $mo_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.mo';
        
        if (!file_exists($mo_file)) {
            $this->printError("✗ MO file does not exist");
            
            // Try to compile it
            if ($this->compileMoFile()) {
                $this->printSuccess("✓ MO file compiled successfully");
            } else {
                $this->errors[] = "Failed to compile MO file";
                return false;
            }
        }
        
        // Check if MO is up to date
        if (filemtime($po_file) > filemtime($mo_file)) {
            $this->printWarning("⚠ MO file is outdated");
            
            // Try to recompile
            if ($this->compileMoFile()) {
                $this->printSuccess("✓ MO file recompiled successfully");
            } else {
                $this->warnings[] = "MO file needs recompilation";
            }
        } else {
            $this->printSuccess("✓ MO file is up to date");
        }
        
        // Check MO file size
        $mo_size = filesize($mo_file);
        $this->printInfo("MO file size: " . $this->formatBytes($mo_size));
        
        if ($mo_size > 1024 * 1024) { // 1MB
            $this->printWarning("⚠ MO file is large (>1MB), may affect performance");
            $this->warnings[] = "Large MO file may affect performance";
        }
        
        return true;
    }
    
    /**
     * Analyze translation coverage
     */
    private function analyzeTranslationCoverage() {
        $this->printStep("Analyzing translation coverage");
        
        $total = $this->stats['pot_strings'];
        $translated = $this->stats['translated'];
        
        if ($total > 0) {
            $percentage = round(($translated / $total) * 100, 2);
        } else {
            $percentage = 0;
        }
        
        $this->stats['percentage'] = $percentage;
        
        // Display coverage bar
        $this->displayProgressBar($percentage);
        
        $this->printInfo("Translation coverage: {$percentage}%");
        $this->printInfo("Minimum required: " . MT_MIN_TRANSLATION_PERCENTAGE . "%");
        
        if ($percentage < MT_MIN_TRANSLATION_PERCENTAGE) {
            $missing = ceil($total * (MT_MIN_TRANSLATION_PERCENTAGE / 100)) - $translated;
            $this->printError("✗ Translation coverage below minimum threshold");
            $this->printError("  Need to translate at least $missing more strings");
            $this->errors[] = "Translation coverage ({$percentage}%) below minimum (" . MT_MIN_TRANSLATION_PERCENTAGE . "%)";
            return false;
        } elseif ($percentage < MT_WARN_TRANSLATION_PERCENTAGE) {
            $this->printWarning("⚠ Translation coverage below warning threshold");
            $this->warnings[] = "Translation coverage below " . MT_WARN_TRANSLATION_PERCENTAGE . "%";
        } else {
            $this->printSuccess("✓ Translation coverage meets requirements");
        }
        
        return true;
    }
    
    /**
     * Validate translation quality
     */
    private function validateTranslationQuality() {
        $this->printStep("Validating translation quality");
        
        $po_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.po';
        $po_content = file_get_contents($po_file);
        $po_strings = $this->parseGettextFile($po_content);
        
        $quality_issues = [];
        
        foreach ($po_strings as $msgid => $entry) {
            if (empty($msgid) || empty($entry['msgstr'])) {
                continue;
            }
            
            // Check for placeholder mismatches
            preg_match_all('/%[sdxfFgGoeEbcuX]/', $msgid, $src_placeholders);
            preg_match_all('/%[sdxfFgGoeEbcuX]/', $entry['msgstr'], $dst_placeholders);
            
            if ($src_placeholders[0] != $dst_placeholders[0]) {
                $quality_issues['placeholders'][] = substr($msgid, 0, 50);
            }
            
            // Check for HTML tag mismatches
            preg_match_all('/<[^>]+>/', $msgid, $src_tags);
            preg_match_all('/<[^>]+>/', $entry['msgstr'], $dst_tags);
            
            if (count($src_tags[0]) != count($dst_tags[0])) {
                $quality_issues['html_tags'][] = substr($msgid, 0, 50);
            }
            
            // Check for untranslated terms (strings that are identical)
            if (strlen($msgid) > 10 && $msgid === $entry['msgstr']) {
                $quality_issues['identical'][] = substr($msgid, 0, 50);
            }
        }
        
        $has_issues = false;
        
        if (!empty($quality_issues['placeholders'])) {
            $count = count($quality_issues['placeholders']);
            $this->printWarning("⚠ Found $count placeholder mismatches");
            $this->warnings[] = "$count translations have placeholder mismatches";
            $has_issues = true;
            
            if ($this->verbose) {
                foreach (array_slice($quality_issues['placeholders'], 0, 3) as $string) {
                    $this->printInfo("  - $string...");
                }
            }
        }
        
        if (!empty($quality_issues['html_tags'])) {
            $count = count($quality_issues['html_tags']);
            $this->printWarning("⚠ Found $count HTML tag mismatches");
            $this->warnings[] = "$count translations have HTML tag mismatches";
            $has_issues = true;
        }
        
        if (!empty($quality_issues['identical'])) {
            $count = count($quality_issues['identical']);
            $this->printWarning("⚠ Found $count potentially untranslated strings");
            $this->warnings[] = "$count strings appear to be untranslated (identical to source)";
            $has_issues = true;
        }
        
        if (!$has_issues) {
            $this->printSuccess("✓ Translation quality checks passed");
        }
        
        return true;
    }
    
    /**
     * Check critical translations
     */
    private function checkCriticalTranslations() {
        $this->printStep("Checking critical translations");
        
        $critical_terms = [
            'Submit' => 'Absenden',
            'Save' => 'Speichern',
            'Cancel' => 'Abbrechen',
            'Delete' => 'Löschen',
            'Error' => 'Fehler',
            'Success' => 'Erfolg',
            'Warning' => 'Warnung',
            'Loading' => 'Laden',
            'Email' => 'E-Mail',
            'Password' => 'Passwort',
            'Login' => 'Anmelden',
            'Logout' => 'Abmelden',
            'Dashboard' => 'Dashboard',
            'Settings' => 'Einstellungen',
            'Profile' => 'Profil',
            'Search' => 'Suchen',
            'Filter' => 'Filtern',
            'Export' => 'Exportieren',
            'Import' => 'Importieren',
            'Download' => 'Herunterladen'
        ];
        
        $po_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.po';
        $po_content = file_get_contents($po_file);
        
        $missing_critical = [];
        
        foreach ($critical_terms as $english => $expected_german) {
            // Check if the term exists and is translated
            if (preg_match('/msgid\s+"' . preg_quote($english, '/') . '"/', $po_content)) {
                if (!preg_match('/msgid\s+"' . preg_quote($english, '/') . '"\s*\nmsgstr\s+"[^"]+"/m', $po_content)) {
                    $missing_critical[] = $english;
                }
            }
        }
        
        if (!empty($missing_critical)) {
            $this->printError("✗ Critical terms not translated:");
            foreach ($missing_critical as $term) {
                $this->printError("  - $term");
            }
            $this->errors[] = count($missing_critical) . " critical terms are not translated";
            return false;
        }
        
        $this->printSuccess("✓ All critical terms are translated");
        return true;
    }
    
    /**
     * Check performance
     */
    private function checkPerformance() {
        $this->printStep("Checking translation performance impact");
        
        $mo_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.mo';
        $mo_size = filesize($mo_file);
        
        // Check file size
        if ($mo_size > 2 * 1024 * 1024) { // 2MB
            $this->printError("✗ MO file too large (" . $this->formatBytes($mo_size) . ")");
            $this->errors[] = "MO file exceeds 2MB limit";
            return false;
        } elseif ($mo_size > 1024 * 1024) { // 1MB
            $this->printWarning("⚠ MO file is large (" . $this->formatBytes($mo_size) . ")");
            $this->warnings[] = "Consider optimizing translations to reduce file size";
        } else {
            $this->printSuccess("✓ MO file size is optimal (" . $this->formatBytes($mo_size) . ")");
        }
        
        // Check string count
        if ($this->stats['pot_strings'] > 5000) {
            $this->printWarning("⚠ Large number of translatable strings ({$this->stats['pot_strings']})");
            $this->warnings[] = "Consider splitting translations into modules";
        }
        
        return true;
    }
    
    /**
     * Display results
     */
    private function displayResults() {
        echo "\n" . Colors::colorize(str_repeat('=', 60), Colors::BLUE) . "\n";
        echo Colors::colorize("VALIDATION SUMMARY", Colors::BLUE) . "\n";
        echo Colors::colorize(str_repeat('=', 60), Colors::BLUE) . "\n\n";
        
        // Statistics
        echo Colors::colorize("Translation Statistics:", Colors::CYAN) . "\n";
        echo "  Total Strings: {$this->stats['pot_strings']}\n";
        echo "  Translated: {$this->stats['translated']} ({$this->stats['percentage']}%)\n";
        echo "  Untranslated: {$this->stats['untranslated']}\n";
        echo "  Fuzzy: {$this->stats['fuzzy']}\n";
        
        // Errors
        if (!empty($this->errors)) {
            echo "\n" . Colors::colorize("Errors (" . count($this->errors) . "):", Colors::RED) . "\n";
            foreach ($this->errors as $error) {
                echo Colors::colorize("  ✗ $error", Colors::RED) . "\n";
            }
        }
        
        // Warnings
        if (!empty($this->warnings)) {
            echo "\n" . Colors::colorize("Warnings (" . count($this->warnings) . "):", Colors::YELLOW) . "\n";
            foreach ($this->warnings as $warning) {
                echo Colors::colorize("  ⚠ $warning", Colors::YELLOW) . "\n";
            }
        }
        
        // Final status
        echo "\n" . Colors::colorize(str_repeat('-', 60), Colors::BLUE) . "\n";
        
        if (empty($this->errors)) {
            if (empty($this->warnings)) {
                echo Colors::colorize("STATUS: READY FOR DEPLOYMENT ✓", Colors::GREEN) . "\n";
            } else {
                echo Colors::colorize("STATUS: DEPLOYMENT ALLOWED (with warnings)", Colors::YELLOW) . "\n";
            }
        } else {
            echo Colors::colorize("STATUS: DEPLOYMENT BLOCKED ✗", Colors::RED) . "\n";
        }
        
        echo Colors::colorize(str_repeat('=', 60), Colors::BLUE) . "\n";
    }
    
    /**
     * Display progress bar
     */
    private function displayProgressBar($percentage) {
        $bar_length = 50;
        $filled = round($bar_length * $percentage / 100);
        $empty = $bar_length - $filled;
        
        $bar = '[';
        $bar .= str_repeat('█', $filled);
        $bar .= str_repeat('░', $empty);
        $bar .= ']';
        
        if ($percentage >= MT_MIN_TRANSLATION_PERCENTAGE) {
            $color = Colors::GREEN;
        } elseif ($percentage >= 80) {
            $color = Colors::YELLOW;
        } else {
            $color = Colors::RED;
        }
        
        echo Colors::colorize($bar, $color) . " {$percentage}%\n";
    }
    
    /**
     * Parse gettext file
     */
    private function parseGettextFile($content) {
        $entries = [];
        $current_entry = null;
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = rtrim($line);
            
            if (empty($line)) {
                if ($current_entry && !empty($current_entry['msgid'])) {
                    $entries[$current_entry['msgid']] = $current_entry;
                }
                $current_entry = null;
                continue;
            }
            
            if (strpos($line, '#,') === 0) {
                if (!$current_entry) {
                    $current_entry = ['msgid' => '', 'msgstr' => '', 'flags' => []];
                }
                $flags = trim(substr($line, 2));
                $current_entry['flags'] = explode(',', $flags);
            } elseif (strpos($line, 'msgid "') === 0) {
                if (!$current_entry) {
                    $current_entry = ['msgid' => '', 'msgstr' => '', 'flags' => []];
                }
                $current_entry['msgid'] = $this->extractString($line, 'msgid');
            } elseif (strpos($line, 'msgstr "') === 0) {
                if ($current_entry) {
                    $current_entry['msgstr'] = $this->extractString($line, 'msgstr');
                }
            }
        }
        
        if ($current_entry && !empty($current_entry['msgid'])) {
            $entries[$current_entry['msgid']] = $current_entry;
        }
        
        return $entries;
    }
    
    /**
     * Extract string from gettext line
     */
    private function extractString($line, $prefix) {
        $pattern = '/^' . preg_quote($prefix, '/') . '\s+"(.*)"/';
        if (preg_match($pattern, $line, $matches)) {
            return stripcslashes($matches[1]);
        }
        return '';
    }
    
    /**
     * Compile MO file
     */
    private function compileMoFile() {
        $po_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.po';
        $mo_file = MT_LANGUAGES_DIR . '/mobility-trailblazers-de_DE.mo';
        
        if ($this->commandExists('msgfmt')) {
            exec("msgfmt -o " . escapeshellarg($mo_file) . " " . escapeshellarg($po_file) . " 2>&1", $output, $return_code);
            return $return_code === 0;
        }
        
        // Fallback to PHP script
        $script = MT_PROJECT_ROOT . '/scripts/german-translation-automation.php';
        if (file_exists($script)) {
            exec("php " . escapeshellarg($script) . " compile 2>&1", $output, $return_code);
            return $return_code === 0;
        }
        
        return false;
    }
    
    /**
     * Check if command exists
     */
    private function commandExists($command) {
        $which = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        exec("$which $command 2>/dev/null", $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * Format bytes
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Print step
     */
    private function printStep($message) {
        echo "\n" . Colors::colorize("▶ $message", Colors::CYAN) . "\n";
    }
    
    /**
     * Print info
     */
    private function printInfo($message) {
        if ($this->verbose) {
            echo "  $message\n";
        }
    }
    
    /**
     * Print success
     */
    private function printSuccess($message) {
        echo Colors::colorize("  $message", Colors::GREEN) . "\n";
    }
    
    /**
     * Print warning
     */
    private function printWarning($message) {
        echo Colors::colorize("  $message", Colors::YELLOW) . "\n";
    }
    
    /**
     * Print error
     */
    private function printError($message) {
        echo Colors::colorize("  $message", Colors::RED) . "\n";
    }
    
    /**
     * Success exit
     */
    private function success() {
        echo "\n" . Colors::colorize("✓ Deployment validation passed!", Colors::GREEN) . "\n";
        exit(0);
    }
    
    /**
     * Failure exit
     */
    private function fail($message) {
        echo "\n" . Colors::colorize("✗ $message", Colors::RED) . "\n";
        echo Colors::colorize("Deployment cannot proceed.", Colors::RED) . "\n";
        exit(1);
    }
}

// Parse command line arguments
$options = getopt('hvf', ['help', 'verbose', 'force']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Translation Deployment Validator

USAGE:
    php validate-translation-deployment.php [options]

OPTIONS:
    -h, --help      Show this help message
    -v, --verbose   Show detailed output
    -f, --force     Force deployment even with warnings

DESCRIPTION:
    This script validates that translations meet the required standards
    before allowing deployment to production. It checks:
    
    • Translation file existence and validity
    • Translation coverage percentage (minimum 95%)
    • Translation quality (placeholders, HTML tags)
    • Critical terms are translated
    • Performance impact

EXIT CODES:
    0 - Validation passed
    1 - Validation failed
    2 - Script error

EXAMPLES:
    # Standard validation
    php validate-translation-deployment.php
    
    # Verbose mode
    php validate-translation-deployment.php -v
    
    # Force deployment with warnings
    php validate-translation-deployment.php -f

HELP;
    exit(0);
}

$verbose = isset($options['v']) || isset($options['verbose']);
$force = isset($options['f']) || isset($options['force']);

// Run validation
$validator = new TranslationDeploymentValidator($verbose, $force);
$validator->validate();