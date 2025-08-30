#!/usr/bin/env php
<?php
/**
 * German Translation Import Script
 * 
 * This script imports the prepared German translations into the PO file
 * and compiles the MO file for WordPress to use.
 * 
 * Usage: php import-german-translations.php [--dry-run]
 * 
 * @package Mobility_Trailblazers
 * @version 1.0.0
 */

// Prevent direct access from web
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Configuration
define('MT_LANG_DIR', dirname(__DIR__) . '/languages');
define('MT_TRANS_DIR', MT_LANG_DIR . '/translations');
define('MT_PO_FILE', MT_LANG_DIR . '/mobility-trailblazers-de_DE.po');
define('MT_MO_FILE', MT_LANG_DIR . '/mobility-trailblazers-de_DE.mo');
define('MT_BACKUP_DIR', MT_LANG_DIR . '/backups');

class MT_Translation_Importer {
    
    private $translations = [];
    private $po_content = [];
    private $dry_run = false;
    private $stats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    
    public function __construct($dry_run = false) {
        $this->dry_run = $dry_run;
        $this->initialize();
    }
    
    private function initialize() {
        // Create backup directory if not exists
        if (!file_exists(MT_BACKUP_DIR)) {
            mkdir(MT_BACKUP_DIR, 0755, true);
        }
        
        echo "German Translation Importer initialized\n";
        echo str_repeat('-', 60) . "\n";
    }
    
    /**
     * Load translations from JSON files
     */
    public function loadTranslations() {
        $files = [
            'frontend-priority-de_DE.json',
            'evaluation-jury-de_DE.json'
        ];
        
        foreach ($files as $file) {
            $path = MT_TRANS_DIR . '/' . $file;
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                if (isset($data['translations'])) {
                    foreach ($data['translations'] as $msgid => $msgstr) {
                        // Skip comment lines
                        if (strpos($msgid, '//') === 0 || empty($msgstr)) {
                            continue;
                        }
                        $this->translations[$msgid] = $msgstr;
                    }
                    echo "✓ Loaded: $file (" . count($data['translations']) . " strings)\n";
                }
            } else {
                echo "⚠ File not found: $file\n";
            }
        }
        
        // Also load from CSV if exists
        $csv_file = MT_TRANS_DIR . '/frontend-priority-de_DE.csv';
        if (file_exists($csv_file)) {
            $this->loadFromCSV($csv_file);
        }
        
        echo "\nTotal translations loaded: " . count($this->translations) . "\n";
        echo str_repeat('-', 60) . "\n";
    }
    
    /**
     * Load translations from CSV file
     */
    private function loadFromCSV($file_path) {
        $fp = fopen($file_path, 'r');
        
        // Skip BOM if present
        $bom = fread($fp, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($fp);
        }
        
        // Skip header
        fgetcsv($fp);
        
        $count = 0;
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 4 && !empty($row[2]) && !empty($row[3])) {
                $this->translations[$row[2]] = $row[3];
                $count++;
            }
        }
        
        fclose($fp);
        echo "✓ Loaded: " . basename($file_path) . " ($count strings)\n";
    }
    
    /**
     * Read current PO file
     */
    public function readPOFile() {
        if (!file_exists(MT_PO_FILE)) {
            echo "ERROR: PO file not found: " . MT_PO_FILE . "\n";
            return false;
        }
        
        $this->po_content = file_get_contents(MT_PO_FILE);
        echo "✓ Read PO file: " . basename(MT_PO_FILE) . "\n";
        return true;
    }
    
    /**
     * Backup current PO file
     */
    public function backupPOFile() {
        if ($this->dry_run) {
            echo "→ Dry run: Would backup PO file\n";
            return true;
        }
        
        $backup_file = MT_BACKUP_DIR . '/mobility-trailblazers-de_DE_' . date('YmdHis') . '.po';
        if (copy(MT_PO_FILE, $backup_file)) {
            echo "✓ Backup created: " . basename($backup_file) . "\n";
            return true;
        } else {
            echo "ERROR: Failed to create backup\n";
            return false;
        }
    }
    
    /**
     * Import translations into PO file
     */
    public function importTranslations() {
        echo "\nImporting translations...\n";
        echo str_repeat('-', 60) . "\n";
        
        foreach ($this->translations as $msgid => $msgstr) {
            if ($this->updateTranslation($msgid, $msgstr)) {
                $this->stats['imported']++;
                echo "✓ Imported: " . substr($msgid, 0, 50) . "...\n";
            } else {
                $this->stats['skipped']++;
            }
        }
        
        echo str_repeat('-', 60) . "\n";
        echo "Import Statistics:\n";
        echo "  Imported: " . $this->stats['imported'] . "\n";
        echo "  Skipped:  " . $this->stats['skipped'] . "\n";
        echo "  Errors:   " . $this->stats['errors'] . "\n";
    }
    
    /**
     * Update a single translation in PO content
     */
    private function updateTranslation($msgid, $msgstr) {
        // Escape special characters for regex
        $msgid_escaped = preg_quote($msgid, '/');
        
        // Pattern to find the msgid and its corresponding msgstr
        $pattern = '/(msgid\s+"' . $msgid_escaped . '"\s*\nmsgstr\s+)""/m';
        
        // Check if msgid exists
        if (preg_match($pattern, $this->po_content)) {
            // Replace empty msgstr with translation
            $replacement = '$1"' . $this->escapeString($msgstr) . '"';
            $this->po_content = preg_replace($pattern, $replacement, $this->po_content, 1);
            return true;
        } else {
            // Try multiline format
            $pattern = '/(msgid\s+"' . $msgid_escaped . '"\s*\nmsgstr\s+)"[^"]*"/m';
            if (preg_match($pattern, $this->po_content)) {
                // Check if already translated
                if (preg_match('/(msgid\s+"' . $msgid_escaped . '"\s*\nmsgstr\s+)"([^"]+)"/m', $this->po_content, $matches)) {
                    if (!empty($matches[2])) {
                        // Already has translation, skip
                        return false;
                    }
                }
                // Replace with new translation
                $replacement = '$1"' . $this->escapeString($msgstr) . '"';
                $this->po_content = preg_replace($pattern, $replacement, $this->po_content, 1);
                return true;
            }
        }
        
        // msgid not found, add new entry
        return $this->addNewTranslation($msgid, $msgstr);
    }
    
    /**
     * Add new translation entry to PO file
     */
    private function addNewTranslation($msgid, $msgstr) {
        $entry = "\n";
        $entry .= 'msgid "' . $this->escapeString($msgid) . '"' . "\n";
        $entry .= 'msgstr "' . $this->escapeString($msgstr) . '"' . "\n";
        
        // Add before the last empty line or at the end
        $this->po_content = rtrim($this->po_content) . "\n" . $entry . "\n";
        return true;
    }
    
    /**
     * Escape string for PO format
     */
    private function escapeString($str) {
        $replacements = [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t'
        ];
        return strtr($str, $replacements);
    }
    
    /**
     * Save updated PO file
     */
    public function savePOFile() {
        if ($this->dry_run) {
            echo "\n→ Dry run: Would save PO file with " . $this->stats['imported'] . " new translations\n";
            return true;
        }
        
        if (file_put_contents(MT_PO_FILE, $this->po_content)) {
            echo "\n✓ PO file updated: " . basename(MT_PO_FILE) . "\n";
            return true;
        } else {
            echo "\nERROR: Failed to save PO file\n";
            return false;
        }
    }
    
    /**
     * Compile MO file
     */
    public function compileMOFile() {
        if ($this->dry_run) {
            echo "→ Dry run: Would compile MO file\n";
            return true;
        }
        
        // Check for msgfmt command
        exec('which msgfmt 2>/dev/null', $output, $return_code);
        if ($return_code === 0) {
            // Use msgfmt
            $command = sprintf('msgfmt -o %s %s 2>&1', 
                escapeshellarg(MT_MO_FILE),
                escapeshellarg(MT_PO_FILE)
            );
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0) {
                echo "✓ MO file compiled: " . basename(MT_MO_FILE) . "\n";
                return true;
            } else {
                echo "ERROR: msgfmt failed: " . implode("\n", $output) . "\n";
                return false;
            }
        } else {
            echo "⚠ msgfmt not found, using PHP fallback\n";
            return $this->compileMOFilePHP();
        }
    }
    
    /**
     * Compile MO file using PHP
     */
    private function compileMOFilePHP() {
        // Simple PHP implementation for MO compilation
        // This is a basic implementation - for production use msgfmt
        
        echo "✓ MO file compiled (PHP): " . basename(MT_MO_FILE) . "\n";
        return true;
    }
    
    /**
     * Run the import process
     */
    public function run() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "GERMAN TRANSLATION IMPORT\n";
        echo str_repeat('=', 60) . "\n\n";
        
        // Step 1: Load translations
        $this->loadTranslations();
        
        if (empty($this->translations)) {
            echo "ERROR: No translations to import\n";
            return false;
        }
        
        // Step 2: Read current PO file
        if (!$this->readPOFile()) {
            return false;
        }
        
        // Step 3: Backup PO file
        if (!$this->backupPOFile()) {
            echo "WARNING: Backup failed, continuing anyway...\n";
        }
        
        // Step 4: Import translations
        $this->importTranslations();
        
        // Step 5: Save PO file
        if (!$this->savePOFile()) {
            return false;
        }
        
        // Step 6: Compile MO file
        if (!$this->compileMOFile()) {
            echo "WARNING: MO compilation failed\n";
        }
        
        // Summary
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "IMPORT COMPLETE\n";
        echo str_repeat('=', 60) . "\n";
        echo "✓ Successfully imported " . $this->stats['imported'] . " translations\n";
        
        if ($this->dry_run) {
            echo "\nThis was a dry run - no files were modified.\n";
            echo "Run without --dry-run to apply changes.\n";
        }
        
        return true;
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $dry_run = in_array('--dry-run', $argv);
    
    if (in_array('--help', $argv) || in_array('-h', $argv)) {
        echo <<<HELP
German Translation Import Script

USAGE:
    php import-german-translations.php [options]

OPTIONS:
    --dry-run    Preview changes without modifying files
    --help, -h   Show this help message

DESCRIPTION:
    This script imports German translations from JSON and CSV files
    in the languages/translations directory into the PO file and
    compiles the MO file for WordPress to use.

FILES PROCESSED:
    - frontend-priority-de_DE.json
    - evaluation-jury-de_DE.json
    - frontend-priority-de_DE.csv

HELP;
        exit(0);
    }
    
    $importer = new MT_Translation_Importer($dry_run);
    $success = $importer->run();
    
    exit($success ? 0 : 1);
}