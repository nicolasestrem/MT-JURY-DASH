#!/usr/bin/env php
<?php
/**
 * German Translation Automation Script for Mobility Trailblazers
 * 
 * This script automates the German translation completion process:
 * - Extracts untranslated strings from POT file
 * - Organizes by priority (frontend > admin > debug)
 * - Prepares for batch translation
 * - Updates PO file and compiles MO file
 * - Validates translation completeness
 * 
 * Usage: php german-translation-automation.php [command] [options]
 * Commands:
 *   analyze    - Analyze translation status
 *   extract    - Extract untranslated strings
 *   translate  - Translate using DeepL API (requires API key)
 *   import     - Import translations from CSV/JSON
 *   compile    - Compile PO to MO
 *   validate   - Validate translation completeness
 *   full       - Run full automation workflow
 * 
 * @package    Mobility_Trailblazers
 * @subpackage Scripts
 * @version    1.0.0
 * @author     Nicolas Estrem
 */

// Prevent direct access from web
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Configuration
define('MT_LANG_DIR', dirname(__DIR__) . '/languages');
define('MT_POT_FILE', MT_LANG_DIR . '/mobility-trailblazers.pot');
define('MT_PO_FILE', MT_LANG_DIR . '/mobility-trailblazers-de_DE.po');
define('MT_MO_FILE', MT_LANG_DIR . '/mobility-trailblazers-de_DE.mo');
define('MT_BACKUP_DIR', MT_LANG_DIR . '/backups');
define('MT_EXPORT_DIR', dirname(__DIR__) . '/exports');
define('MT_LOG_FILE', dirname(__DIR__) . '/logs/translation-automation.log');

// DeepL API configuration (set via environment or config file)
define('DEEPL_API_KEY', getenv('DEEPL_API_KEY') ?: '');
define('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate');

/**
 * Main Translation Automation Class
 */
class MT_Translation_Automation {
    
    private $pot_strings = [];
    private $po_strings = [];
    private $untranslated = [];
    private $stats = [];
    private $errors = [];
    private $verbose = false;
    private $dry_run = false;
    
    /**
     * Constructor
     */
    public function __construct($verbose = false, $dry_run = false) {
        $this->verbose = $verbose;
        $this->dry_run = $dry_run;
        $this->initialize();
    }
    
    /**
     * Initialize directories and logging
     */
    private function initialize() {
        // Create necessary directories
        if (!file_exists(MT_BACKUP_DIR)) {
            mkdir(MT_BACKUP_DIR, 0755, true);
        }
        if (!file_exists(MT_EXPORT_DIR)) {
            mkdir(MT_EXPORT_DIR, 0755, true);
        }
        if (!file_exists(dirname(MT_LOG_FILE))) {
            mkdir(dirname(MT_LOG_FILE), 0755, true);
        }
        
        $this->log("Translation Automation Script initialized");
    }
    
    /**
     * Log message
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] [$level] $message\n";
        
        if ($this->verbose || $level === 'ERROR') {
            echo $log_message;
        }
        
        file_put_contents(MT_LOG_FILE, $log_message, FILE_APPEND);
    }
    
    /**
     * Parse POT/PO file
     */
    private function parse_gettext_file($file_path) {
        if (!file_exists($file_path)) {
            $this->log("File not found: $file_path", 'ERROR');
            return [];
        }
        
        $content = file_get_contents($file_path);
        $translations = [];
        $current_entry = null;
        $current_field = null;
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = rtrim($line);
            
            // Skip empty lines
            if (empty($line)) {
                if ($current_entry && !empty($current_entry['msgid'])) {
                    $key = $current_entry['msgid'];
                    $translations[$key] = $current_entry;
                }
                $current_entry = null;
                $current_field = null;
                continue;
            }
            
            // Handle comments
            if (strpos($line, '#') === 0) {
                if (!$current_entry) {
                    $current_entry = [
                        'comments' => [],
                        'references' => [],
                        'flags' => [],
                        'msgid' => '',
                        'msgstr' => '',
                        'msgid_plural' => '',
                        'msgstr_plural' => []
                    ];
                }
                
                if (strpos($line, '#:') === 0) {
                    // Reference comment
                    $current_entry['references'][] = trim(substr($line, 2));
                } elseif (strpos($line, '#,') === 0) {
                    // Flag comment
                    $current_entry['flags'][] = trim(substr($line, 2));
                } elseif (strpos($line, '#.') === 0) {
                    // Extracted comment
                    $current_entry['comments'][] = trim(substr($line, 2));
                }
                continue;
            }
            
            // Handle msgid
            if (strpos($line, 'msgid "') === 0) {
                if (!$current_entry) {
                    $current_entry = [
                        'comments' => [],
                        'references' => [],
                        'flags' => [],
                        'msgid' => '',
                        'msgstr' => '',
                        'msgid_plural' => '',
                        'msgstr_plural' => []
                    ];
                }
                $current_field = 'msgid';
                $current_entry['msgid'] = $this->extract_string($line, 'msgid');
                continue;
            }
            
            // Handle msgid_plural
            if (strpos($line, 'msgid_plural "') === 0) {
                $current_field = 'msgid_plural';
                $current_entry['msgid_plural'] = $this->extract_string($line, 'msgid_plural');
                continue;
            }
            
            // Handle msgstr
            if (strpos($line, 'msgstr "') === 0) {
                $current_field = 'msgstr';
                $current_entry['msgstr'] = $this->extract_string($line, 'msgstr');
                continue;
            }
            
            // Handle msgstr[n]
            if (preg_match('/^msgstr\[(\d+)\] "/', $line, $matches)) {
                $index = (int)$matches[1];
                $current_field = 'msgstr_plural';
                $current_entry['msgstr_plural'][$index] = $this->extract_string($line, "msgstr[$index]");
                continue;
            }
            
            // Handle continuation lines
            if (strpos($line, '"') === 0 && $current_field) {
                $content = $this->extract_continuation_string($line);
                if ($current_field === 'msgstr_plural') {
                    $last_index = count($current_entry['msgstr_plural']) - 1;
                    $current_entry['msgstr_plural'][$last_index] .= $content;
                } else {
                    $current_entry[$current_field] .= $content;
                }
            }
        }
        
        // Add last entry
        if ($current_entry && !empty($current_entry['msgid'])) {
            $key = $current_entry['msgid'];
            $translations[$key] = $current_entry;
        }
        
        return $translations;
    }
    
    /**
     * Extract string from gettext line
     */
    private function extract_string($line, $prefix) {
        $pattern = '/^' . preg_quote($prefix, '/') . '(\[\d+\])?\s+"(.*)"/';
        if (preg_match($pattern, $line, $matches)) {
            return $this->unescape_string($matches[2]);
        }
        return '';
    }
    
    /**
     * Extract continuation string
     */
    private function extract_continuation_string($line) {
        if (preg_match('/^"(.*)"/', $line, $matches)) {
            return $this->unescape_string($matches[1]);
        }
        return '';
    }
    
    /**
     * Unescape gettext string
     */
    private function unescape_string($str) {
        $replacements = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\"' => '"',
            '\\\\' => '\\'
        ];
        return strtr($str, $replacements);
    }
    
    /**
     * Escape gettext string
     */
    private function escape_string($str) {
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
     * Analyze translation status
     */
    public function analyze() {
        $this->log("Analyzing translation status...");
        
        // Parse files
        $this->pot_strings = $this->parse_gettext_file(MT_POT_FILE);
        $this->po_strings = $this->parse_gettext_file(MT_PO_FILE);
        
        // Calculate statistics
        $total_strings = count($this->pot_strings);
        $translated = 0;
        $untranslated = 0;
        $fuzzy = 0;
        
        foreach ($this->pot_strings as $msgid => $entry) {
            if (empty($msgid)) continue;
            
            if (isset($this->po_strings[$msgid])) {
                $po_entry = $this->po_strings[$msgid];
                if (!empty($po_entry['msgstr'])) {
                    if (in_array('fuzzy', $po_entry['flags'])) {
                        $fuzzy++;
                    } else {
                        $translated++;
                    }
                } else {
                    $untranslated++;
                    $this->untranslated[$msgid] = $entry;
                }
            } else {
                $untranslated++;
                $this->untranslated[$msgid] = $entry;
            }
        }
        
        // Store statistics
        $this->stats = [
            'total' => $total_strings,
            'translated' => $translated,
            'untranslated' => $untranslated,
            'fuzzy' => $fuzzy,
            'percentage' => $total_strings > 0 ? round(($translated / $total_strings) * 100, 2) : 0
        ];
        
        // Display results
        $this->display_stats();
        
        return $this->stats;
    }
    
    /**
     * Display statistics
     */
    private function display_stats() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TRANSLATION STATUS REPORT\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Source POT file: " . basename(MT_POT_FILE) . "\n";
        echo "Target PO file:  " . basename(MT_PO_FILE) . "\n\n";
        
        echo "Statistics:\n";
        echo str_repeat('-', 40) . "\n";
        echo sprintf("Total strings:       %d\n", $this->stats['total']);
        echo sprintf("Translated:          %d (%.1f%%)\n", 
            $this->stats['translated'], 
            $this->stats['percentage']);
        echo sprintf("Untranslated:        %d (%.1f%%)\n", 
            $this->stats['untranslated'],
            $this->stats['total'] > 0 ? ($this->stats['untranslated'] / $this->stats['total']) * 100 : 0);
        echo sprintf("Fuzzy:               %d\n", $this->stats['fuzzy']);
        echo "\n";
        
        if ($this->stats['untranslated'] > 0) {
            echo "Action required: Translate " . $this->stats['untranslated'] . " missing strings\n";
        } else {
            echo "Excellent! All strings are translated.\n";
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
    
    /**
     * Extract untranslated strings
     */
    public function extract() {
        $this->log("Extracting untranslated strings...");
        
        // First analyze to get untranslated strings
        $this->analyze();
        
        if (empty($this->untranslated)) {
            $this->log("No untranslated strings found!");
            return;
        }
        
        // Organize by priority
        $prioritized = $this->prioritize_strings($this->untranslated);
        
        // Export to different formats
        $timestamp = date('Y-m-d_His');
        
        // Export as CSV
        $csv_file = MT_EXPORT_DIR . "/untranslated_strings_$timestamp.csv";
        $this->export_csv($prioritized, $csv_file);
        
        // Export as JSON
        $json_file = MT_EXPORT_DIR . "/untranslated_strings_$timestamp.json";
        $this->export_json($prioritized, $json_file);
        
        // Export as translation template
        $template_file = MT_EXPORT_DIR . "/translation_template_$timestamp.txt";
        $this->export_template($prioritized, $template_file);
        
        $this->log("Extracted " . count($this->untranslated) . " untranslated strings");
        $this->log("Files exported to: " . MT_EXPORT_DIR);
        
        return $prioritized;
    }
    
    /**
     * Prioritize strings by context
     */
    private function prioritize_strings($strings) {
        $prioritized = [
            'frontend' => [],
            'admin' => [],
            'debug' => [],
            'other' => []
        ];
        
        foreach ($strings as $msgid => $entry) {
            $category = $this->categorize_string($entry);
            $prioritized[$category][$msgid] = $entry;
        }
        
        // Display categorization summary
        echo "\nString Categorization:\n";
        echo str_repeat('-', 40) . "\n";
        foreach ($prioritized as $category => $items) {
            $count = count($items);
            if ($count > 0) {
                echo sprintf("%-15s: %d strings\n", ucfirst($category), $count);
            }
        }
        echo "\n";
        
        return $prioritized;
    }
    
    /**
     * Categorize string based on references
     */
    private function categorize_string($entry) {
        $references = implode(' ', $entry['references']);
        
        // Check for frontend references
        if (strpos($references, 'templates/frontend') !== false ||
            strpos($references, 'public/') !== false ||
            strpos($references, 'shortcodes') !== false ||
            strpos($references, 'widgets') !== false) {
            return 'frontend';
        }
        
        // Check for admin references
        if (strpos($references, 'admin/') !== false ||
            strpos($references, 'templates/admin') !== false ||
            strpos($references, 'includes/admin') !== false) {
            return 'admin';
        }
        
        // Check for debug references
        if (strpos($references, 'debug/') !== false ||
            strpos($references, 'diagnostics') !== false ||
            strpos($references, 'test') !== false) {
            return 'debug';
        }
        
        return 'other';
    }
    
    /**
     * Export to CSV
     */
    private function export_csv($prioritized, $file_path) {
        $fp = fopen($file_path, 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($fp, ['Priority', 'Category', 'Original (English)', 'Translation (German)', 'Context', 'References']);
        
        $priority_order = ['frontend' => 1, 'admin' => 2, 'debug' => 3, 'other' => 4];
        
        foreach ($prioritized as $category => $strings) {
            $priority = $priority_order[$category];
            foreach ($strings as $msgid => $entry) {
                fputcsv($fp, [
                    $priority,
                    ucfirst($category),
                    $msgid,
                    '', // Empty for translation
                    implode('; ', $entry['comments']),
                    implode('; ', $entry['references'])
                ]);
            }
        }
        
        fclose($fp);
        $this->log("CSV exported to: $file_path");
    }
    
    /**
     * Export to JSON
     */
    private function export_json($prioritized, $file_path) {
        $export_data = [
            'metadata' => [
                'total_strings' => array_sum(array_map('count', $prioritized)),
                'export_date' => date('Y-m-d H:i:s'),
                'source_file' => basename(MT_POT_FILE),
                'categories' => array_map('count', $prioritized)
            ],
            'strings' => $prioritized
        ];
        
        file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->log("JSON exported to: $file_path");
    }
    
    /**
     * Export translation template
     */
    private function export_template($prioritized, $file_path) {
        $content = "MOBILITY TRAILBLAZERS - GERMAN TRANSLATION TEMPLATE\n";
        $content .= str_repeat('=', 60) . "\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Total strings to translate: " . array_sum(array_map('count', $prioritized)) . "\n\n";
        
        foreach ($prioritized as $category => $strings) {
            if (empty($strings)) continue;
            
            $content .= "\n" . str_repeat('=', 60) . "\n";
            $content .= strtoupper($category) . " STRINGS (" . count($strings) . " total)\n";
            $content .= str_repeat('=', 60) . "\n\n";
            
            $index = 1;
            foreach ($strings as $msgid => $entry) {
                $content .= "[$category-$index]\n";
                $content .= "English: $msgid\n";
                $content .= "German:  [TRANSLATE HERE]\n";
                
                if (!empty($entry['comments'])) {
                    $content .= "Context: " . implode('; ', $entry['comments']) . "\n";
                }
                
                if (!empty($entry['references'])) {
                    $content .= "Used in: " . implode(', ', array_slice($entry['references'], 0, 3));
                    if (count($entry['references']) > 3) {
                        $content .= " (+" . (count($entry['references']) - 3) . " more)";
                    }
                    $content .= "\n";
                }
                
                $content .= "\n";
                $index++;
            }
        }
        
        file_put_contents($file_path, $content);
        $this->log("Template exported to: $file_path");
    }
    
    /**
     * Translate using DeepL API
     */
    public function translate($batch_size = 50) {
        if (empty(DEEPL_API_KEY)) {
            $this->log("DeepL API key not configured. Set DEEPL_API_KEY environment variable.", 'ERROR');
            return false;
        }
        
        $this->log("Starting automatic translation with DeepL...");
        
        // Get untranslated strings
        $this->analyze();
        
        if (empty($this->untranslated)) {
            $this->log("No untranslated strings found!");
            return true;
        }
        
        $prioritized = $this->prioritize_strings($this->untranslated);
        $translations = [];
        $total_translated = 0;
        
        // Process by priority
        foreach (['frontend', 'admin', 'other', 'debug'] as $category) {
            if (empty($prioritized[$category])) continue;
            
            $this->log("Translating $category strings...");
            $strings = array_keys($prioritized[$category]);
            $batches = array_chunk($strings, $batch_size);
            
            foreach ($batches as $batch_index => $batch) {
                $batch_translations = $this->translate_batch($batch);
                if ($batch_translations) {
                    $translations = array_merge($translations, $batch_translations);
                    $total_translated += count($batch_translations);
                    $this->log("Batch " . ($batch_index + 1) . " completed: " . count($batch_translations) . " strings");
                }
                
                // Rate limiting
                if ($batch_index < count($batches) - 1) {
                    sleep(1); // Avoid hitting API rate limits
                }
            }
        }
        
        if ($total_translated > 0) {
            $this->log("Successfully translated $total_translated strings");
            
            // Save translations
            if (!$this->dry_run) {
                $this->import_translations($translations);
            } else {
                $this->log("Dry run mode - translations not saved");
            }
        }
        
        return $translations;
    }
    
    /**
     * Translate batch using DeepL
     */
    private function translate_batch($texts) {
        $ch = curl_init(DEEPL_API_URL);
        
        $post_data = [
            'auth_key' => DEEPL_API_KEY,
            'text' => $texts,
            'source_lang' => 'EN',
            'target_lang' => 'DE',
            'tag_handling' => 'xml',
            'preserve_formatting' => 1
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $this->log("DeepL API error (HTTP $http_code): $response", 'ERROR');
            return false;
        }
        
        $result = json_decode($response, true);
        if (!isset($result['translations'])) {
            $this->log("Invalid DeepL response: $response", 'ERROR');
            return false;
        }
        
        $translations = [];
        foreach ($result['translations'] as $index => $translation) {
            if (isset($texts[$index])) {
                $translations[$texts[$index]] = $translation['text'];
            }
        }
        
        return $translations;
    }
    
    /**
     * Import translations from file
     */
    public function import($file_path) {
        if (!file_exists($file_path)) {
            $this->log("Import file not found: $file_path", 'ERROR');
            return false;
        }
        
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $translations = [];
        
        switch ($extension) {
            case 'csv':
                $translations = $this->import_from_csv($file_path);
                break;
            case 'json':
                $translations = $this->import_from_json($file_path);
                break;
            case 'txt':
                $translations = $this->import_from_template($file_path);
                break;
            default:
                $this->log("Unsupported file format: $extension", 'ERROR');
                return false;
        }
        
        if (empty($translations)) {
            $this->log("No translations found in file", 'ERROR');
            return false;
        }
        
        return $this->import_translations($translations);
    }
    
    /**
     * Import from CSV
     */
    private function import_from_csv($file_path) {
        $translations = [];
        $fp = fopen($file_path, 'r');
        
        // Skip BOM if present
        $bom = fread($fp, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($fp);
        }
        
        // Skip header
        fgetcsv($fp);
        
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 4 && !empty($row[2]) && !empty($row[3])) {
                $translations[$row[2]] = $row[3];
            }
        }
        
        fclose($fp);
        return $translations;
    }
    
    /**
     * Import from JSON
     */
    private function import_from_json($file_path) {
        $data = json_decode(file_get_contents($file_path), true);
        $translations = [];
        
        if (isset($data['translations'])) {
            $translations = $data['translations'];
        } elseif (isset($data['strings'])) {
            foreach ($data['strings'] as $category => $strings) {
                foreach ($strings as $msgid => $entry) {
                    if (isset($entry['translation']) && !empty($entry['translation'])) {
                        $translations[$msgid] = $entry['translation'];
                    }
                }
            }
        }
        
        return $translations;
    }
    
    /**
     * Import from template
     */
    private function import_from_template($file_path) {
        $content = file_get_contents($file_path);
        $translations = [];
        
        // Parse template format
        if (preg_match_all('/English: (.+)\nGerman:\s+(.+)\n/U', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $english = trim($matches[1][$i]);
                $german = trim($matches[2][$i]);
                if (!empty($german) && $german !== '[TRANSLATE HERE]') {
                    $translations[$english] = $german;
                }
            }
        }
        
        return $translations;
    }
    
    /**
     * Import translations into PO file
     */
    private function import_translations($translations) {
        if (empty($translations)) {
            $this->log("No translations to import", 'ERROR');
            return false;
        }
        
        $this->log("Importing " . count($translations) . " translations...");
        
        // Backup current PO file
        $backup_file = MT_BACKUP_DIR . '/mobility-trailblazers-de_DE_' . date('YmdHis') . '.po';
        copy(MT_PO_FILE, $backup_file);
        $this->log("Backup created: $backup_file");
        
        // Parse current files
        $this->pot_strings = $this->parse_gettext_file(MT_POT_FILE);
        $this->po_strings = $this->parse_gettext_file(MT_PO_FILE);
        
        // Merge translations
        $updated = 0;
        foreach ($translations as $msgid => $msgstr) {
            if (isset($this->pot_strings[$msgid])) {
                if (!isset($this->po_strings[$msgid])) {
                    // New translation
                    $this->po_strings[$msgid] = $this->pot_strings[$msgid];
                }
                $this->po_strings[$msgid]['msgstr'] = $msgstr;
                // Remove fuzzy flag if present
                $this->po_strings[$msgid]['flags'] = array_diff($this->po_strings[$msgid]['flags'], ['fuzzy']);
                $updated++;
            }
        }
        
        // Write updated PO file
        if (!$this->dry_run) {
            $this->write_po_file($this->po_strings);
            $this->log("Updated $updated translations in PO file");
            
            // Compile to MO
            $this->compile();
        } else {
            $this->log("Dry run mode - would update $updated translations");
        }
        
        return true;
    }
    
    /**
     * Write PO file
     */
    private function write_po_file($strings) {
        $output = $this->generate_po_header();
        
        foreach ($strings as $msgid => $entry) {
            if (empty($msgid)) continue;
            
            // Comments
            foreach ($entry['comments'] as $comment) {
                $output .= "#. $comment\n";
            }
            
            // References
            foreach ($entry['references'] as $reference) {
                $output .= "#: $reference\n";
            }
            
            // Flags
            if (!empty($entry['flags'])) {
                $output .= "#, " . implode(', ', $entry['flags']) . "\n";
            }
            
            // msgid
            $output .= $this->format_string('msgid', $msgid);
            
            // msgid_plural
            if (!empty($entry['msgid_plural'])) {
                $output .= $this->format_string('msgid_plural', $entry['msgid_plural']);
            }
            
            // msgstr or msgstr[n]
            if (!empty($entry['msgstr_plural'])) {
                foreach ($entry['msgstr_plural'] as $index => $str) {
                    $output .= $this->format_string("msgstr[$index]", $str);
                }
            } else {
                $output .= $this->format_string('msgstr', $entry['msgstr']);
            }
            
            $output .= "\n";
        }
        
        file_put_contents(MT_PO_FILE, $output);
    }
    
    /**
     * Generate PO header
     */
    private function generate_po_header() {
        $header = <<<HEADER
# German translations for Mobility Trailblazers plugin
# Copyright (C) 2025 Mobility Trailblazers
# This file is distributed under the GPL v2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Mobility Trailblazers 2.5.37\\n"
"Report-Msgid-Bugs-To: https://mobilitytrailblazers.com\\n"
"POT-Creation-Date: 2025-08-20 12:00+0000\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: Mobility Trailblazers Team\\n"
"Language-Team: German <de@mobilitytrailblazers.com>\\n"
"Language: de_DE\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"X-Generator: MT Translation Automation 1.0\\n"

HEADER;
        
        return str_replace('YEAR-MO-DA HO:MI+ZONE', date('Y-m-d H:i+O'), $header);
    }
    
    /**
     * Format string for PO file
     */
    private function format_string($key, $value) {
        if (empty($value)) {
            return $key . ' ""' . "\n";
        }
        
        $escaped = $this->escape_string($value);
        $lines = explode('\n', $escaped);
        
        if (count($lines) == 1 && strlen($escaped) < 70) {
            return $key . ' "' . $escaped . '"' . "\n";
        }
        
        // Multi-line format
        $output = $key . ' ""' . "\n";
        foreach ($lines as $i => $line) {
            if ($i < count($lines) - 1) {
                $output .= '"' . $line . '\n"' . "\n";
            } else {
                $output .= '"' . $line . '"' . "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Compile PO to MO
     */
    public function compile() {
        $this->log("Compiling PO to MO file...");
        
        // Check for msgfmt command
        $msgfmt_available = false;
        exec('which msgfmt 2>/dev/null', $output, $return_code);
        if ($return_code === 0) {
            $msgfmt_available = true;
        }
        
        if ($msgfmt_available) {
            // Use msgfmt for best compatibility
            $command = sprintf('msgfmt -o %s %s 2>&1', 
                escapeshellarg(MT_MO_FILE),
                escapeshellarg(MT_PO_FILE)
            );
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0) {
                $this->log("MO file compiled successfully using msgfmt");
                return true;
            } else {
                $this->log("msgfmt error: " . implode("\n", $output), 'ERROR');
            }
        }
        
        // Fallback to PHP implementation
        $this->log("Using PHP implementation to compile MO file");
        
        $po_strings = $this->parse_gettext_file(MT_PO_FILE);
        
        // Create MO file
        $mo = new MO_Writer();
        
        foreach ($po_strings as $msgid => $entry) {
            if (empty($msgid) || empty($entry['msgstr'])) continue;
            
            $mo->add_entry($msgid, $entry['msgstr']);
        }
        
        $mo->write(MT_MO_FILE);
        $this->log("MO file compiled successfully");
        
        return true;
    }
    
    /**
     * Validate translations
     */
    public function validate() {
        $this->log("Validating translations...");
        
        // Analyze current status
        $this->analyze();
        
        $issues = [];
        
        // Check for empty translations
        foreach ($this->po_strings as $msgid => $entry) {
            if (empty($msgid)) continue;
            
            if (empty($entry['msgstr'])) {
                $issues['empty'][] = $msgid;
            }
            
            // Check for fuzzy translations
            if (in_array('fuzzy', $entry['flags'])) {
                $issues['fuzzy'][] = $msgid;
            }
            
            // Check for placeholders mismatch
            if (!empty($entry['msgstr'])) {
                $placeholders_src = $this->extract_placeholders($msgid);
                $placeholders_dst = $this->extract_placeholders($entry['msgstr']);
                
                if ($placeholders_src !== $placeholders_dst) {
                    $issues['placeholders'][] = [
                        'msgid' => $msgid,
                        'expected' => $placeholders_src,
                        'found' => $placeholders_dst
                    ];
                }
            }
            
            // Check for HTML tags mismatch
            if (!empty($entry['msgstr'])) {
                $tags_src = $this->extract_html_tags($msgid);
                $tags_dst = $this->extract_html_tags($entry['msgstr']);
                
                if ($tags_src !== $tags_dst) {
                    $issues['html_tags'][] = [
                        'msgid' => $msgid,
                        'expected' => $tags_src,
                        'found' => $tags_dst
                    ];
                }
            }
        }
        
        // Check MO file
        if (!file_exists(MT_MO_FILE)) {
            $issues['mo_missing'] = true;
        } else {
            $mo_time = filemtime(MT_MO_FILE);
            $po_time = filemtime(MT_PO_FILE);
            if ($po_time > $mo_time) {
                $issues['mo_outdated'] = true;
            }
        }
        
        // Display validation results
        $this->display_validation_results($issues);
        
        return empty($issues);
    }
    
    /**
     * Extract placeholders from string
     */
    private function extract_placeholders($str) {
        preg_match_all('/%[sdxfFgGoeEbcuX]|%\d+\$[sdxfFgGoeEbcuX]/', $str, $matches);
        sort($matches[0]);
        return $matches[0];
    }
    
    /**
     * Extract HTML tags from string
     */
    private function extract_html_tags($str) {
        preg_match_all('/<[^>]+>/', $str, $matches);
        $tags = array_map(function($tag) {
            return preg_replace('/\s+/', ' ', $tag);
        }, $matches[0]);
        sort($tags);
        return $tags;
    }
    
    /**
     * Display validation results
     */
    private function display_validation_results($issues) {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TRANSLATION VALIDATION REPORT\n";
        echo str_repeat('=', 60) . "\n\n";
        
        if (empty($issues)) {
            echo "✓ All validations passed successfully!\n";
            echo "  - No empty translations\n";
            echo "  - No fuzzy translations\n";
            echo "  - All placeholders match\n";
            echo "  - All HTML tags match\n";
            echo "  - MO file is up to date\n";
        } else {
            echo "Issues found:\n\n";
            
            if (isset($issues['empty'])) {
                echo "Empty translations: " . count($issues['empty']) . "\n";
                foreach (array_slice($issues['empty'], 0, 5) as $msgid) {
                    echo "  - " . substr($msgid, 0, 60) . "...\n";
                }
                if (count($issues['empty']) > 5) {
                    echo "  ... and " . (count($issues['empty']) - 5) . " more\n";
                }
                echo "\n";
            }
            
            if (isset($issues['fuzzy'])) {
                echo "Fuzzy translations: " . count($issues['fuzzy']) . "\n";
                foreach (array_slice($issues['fuzzy'], 0, 5) as $msgid) {
                    echo "  - " . substr($msgid, 0, 60) . "...\n";
                }
                echo "\n";
            }
            
            if (isset($issues['placeholders'])) {
                echo "Placeholder mismatches: " . count($issues['placeholders']) . "\n";
                foreach (array_slice($issues['placeholders'], 0, 3) as $issue) {
                    echo "  - " . substr($issue['msgid'], 0, 40) . "...\n";
                    echo "    Expected: " . implode(', ', $issue['expected']) . "\n";
                    echo "    Found: " . implode(', ', $issue['found']) . "\n";
                }
                echo "\n";
            }
            
            if (isset($issues['html_tags'])) {
                echo "HTML tag mismatches: " . count($issues['html_tags']) . "\n";
                foreach (array_slice($issues['html_tags'], 0, 3) as $issue) {
                    echo "  - " . substr($issue['msgid'], 0, 40) . "...\n";
                }
                echo "\n";
            }
            
            if (isset($issues['mo_missing'])) {
                echo "⚠ MO file is missing! Run 'compile' command to generate.\n\n";
            }
            
            if (isset($issues['mo_outdated'])) {
                echo "⚠ MO file is outdated! Run 'compile' command to update.\n\n";
            }
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
    
    /**
     * Run full automation workflow
     */
    public function full() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "MOBILITY TRAILBLAZERS TRANSLATION AUTOMATION\n";
        echo str_repeat('=', 60) . "\n\n";
        
        // Step 1: Analyze
        echo "Step 1: Analyzing current translation status...\n";
        $stats = $this->analyze();
        
        if ($stats['untranslated'] == 0) {
            echo "\n✓ All strings are already translated!\n";
            return true;
        }
        
        // Step 2: Extract
        echo "\nStep 2: Extracting untranslated strings...\n";
        $this->extract();
        
        // Step 3: Translate (if API key available)
        if (!empty(DEEPL_API_KEY)) {
            echo "\nStep 3: Translating with DeepL API...\n";
            $this->translate();
        } else {
            echo "\nStep 3: DeepL API key not configured.\n";
            echo "To enable automatic translation:\n";
            echo "  export DEEPL_API_KEY='your-api-key'\n";
            echo "\nManual translation required. Check exports directory for files.\n";
        }
        
        // Step 4: Compile
        echo "\nStep 4: Compiling MO file...\n";
        $this->compile();
        
        // Step 5: Validate
        echo "\nStep 5: Validating translations...\n";
        $valid = $this->validate();
        
        // Final summary
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "AUTOMATION COMPLETE\n";
        echo str_repeat('=', 60) . "\n";
        
        if ($valid) {
            echo "✓ Translation process completed successfully!\n";
        } else {
            echo "⚠ Translation process completed with issues.\n";
            echo "  Review the validation report above for details.\n";
        }
        
        echo "\nFiles updated:\n";
        echo "  - " . basename(MT_PO_FILE) . "\n";
        echo "  - " . basename(MT_MO_FILE) . "\n";
        
        return $valid;
    }
}

/**
 * Simple MO file writer
 */
class MO_Writer {
    private $entries = [];
    
    public function add_entry($msgid, $msgstr) {
        $this->entries[$msgid] = $msgstr;
    }
    
    public function write($filename) {
        $fp = fopen($filename, 'wb');
        if (!$fp) {
            return false;
        }
        
        // Sort entries by msgid
        ksort($this->entries);
        
        // Header
        $header = [
            'Project-Id-Version' => 'Mobility Trailblazers',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Plural-Forms' => 'nplurals=2; plural=(n != 1);'
        ];
        
        $header_str = '';
        foreach ($header as $key => $value) {
            $header_str .= "$key: $value\n";
        }
        
        // Add header as first entry
        $all_entries = ['' => $header_str] + $this->entries;
        
        $count = count($all_entries);
        $ids = '';
        $strs = '';
        $ids_lengths = [];
        $strs_lengths = [];
        
        foreach ($all_entries as $msgid => $msgstr) {
            $ids_lengths[] = strlen($msgid);
            $strs_lengths[] = strlen($msgstr);
            $ids .= $msgid . "\0";
            $strs .= $msgstr . "\0";
        }
        
        // Calculate offsets
        $key_start = 28 + $count * 16;
        $value_start = $key_start + strlen($ids);
        
        // Write header
        fwrite($fp, pack('L', 0x950412de)); // Magic number
        fwrite($fp, pack('L', 0)); // Version
        fwrite($fp, pack('L', $count)); // Number of strings
        fwrite($fp, pack('L', 28)); // Offset of table with original strings
        fwrite($fp, pack('L', 28 + $count * 8)); // Offset of table with translations
        fwrite($fp, pack('L', 0)); // Size of hash table
        fwrite($fp, pack('L', $key_start + strlen($ids) + strlen($strs))); // Offset of hash table
        
        // Write original string table
        $offset = 0;
        foreach ($ids_lengths as $length) {
            fwrite($fp, pack('L', $length));
            fwrite($fp, pack('L', $key_start + $offset));
            $offset += $length + 1;
        }
        
        // Write translation string table
        $offset = 0;
        foreach ($strs_lengths as $length) {
            fwrite($fp, pack('L', $length));
            fwrite($fp, pack('L', $value_start + $offset));
            $offset += $length + 1;
        }
        
        // Write strings
        fwrite($fp, $ids);
        fwrite($fp, $strs);
        
        fclose($fp);
        return true;
    }
}

/**
 * Main execution
 */
if (php_sapi_name() === 'cli') {
    // Parse command line arguments
    $options = getopt('hvcn', [
        'help',
        'verbose',
        'check',
        'dry-run',
        'batch-size:',
        'api-key:',
        'input:',
        'output:'
    ]);
    
    $command = isset($argv[1]) ? $argv[1] : 'help';
    
    // Handle help
    if ($command === 'help' || isset($options['h']) || isset($options['help'])) {
        echo <<<HELP
Mobility Trailblazers - German Translation Automation Script

USAGE:
    php german-translation-automation.php [command] [options]

COMMANDS:
    analyze     Analyze translation status and display statistics
    extract     Extract untranslated strings to CSV/JSON/TXT files
    translate   Translate using DeepL API (requires API key)
    import      Import translations from CSV/JSON/TXT file
    compile     Compile PO file to MO format
    validate    Validate translation completeness and correctness
    full        Run complete automation workflow

OPTIONS:
    -h, --help          Show this help message
    -v, --verbose       Show detailed output
    -c, --check         Check mode (validate without changes)
    -n, --dry-run       Dry run mode (no file changes)
    --batch-size=N      Number of strings per API batch (default: 50)
    --api-key=KEY       DeepL API key
    --input=FILE        Input file for import command
    --output=DIR        Output directory for exports

EXAMPLES:
    # Analyze current translation status
    php german-translation-automation.php analyze

    # Extract untranslated strings
    php german-translation-automation.php extract

    # Translate with DeepL
    export DEEPL_API_KEY='your-key-here'
    php german-translation-automation.php translate

    # Import translations from CSV
    php german-translation-automation.php import --input=translations.csv

    # Run full automation
    php german-translation-automation.php full --verbose

ENVIRONMENT VARIABLES:
    DEEPL_API_KEY       DeepL API authentication key

OUTPUT FILES:
    exports/            Extracted strings in various formats
    languages/backups/  Automatic backups of PO files
    logs/               Script execution logs

HELP;
        exit(0);
    }
    
    // Initialize automation
    $verbose = isset($options['v']) || isset($options['verbose']);
    $dry_run = isset($options['n']) || isset($options['dry-run']);
    
    $automation = new MT_Translation_Automation($verbose, $dry_run);
    
    // Override API key if provided
    if (isset($options['api-key'])) {
        define('DEEPL_API_KEY', $options['api-key']);
    }
    
    // Execute command
    switch ($command) {
        case 'analyze':
            $automation->analyze();
            break;
            
        case 'extract':
            $automation->extract();
            break;
            
        case 'translate':
            $batch_size = isset($options['batch-size']) ? (int)$options['batch-size'] : 50;
            $automation->translate($batch_size);
            break;
            
        case 'import':
            if (!isset($options['input'])) {
                echo "Error: --input=FILE required for import command\n";
                exit(1);
            }
            $automation->import($options['input']);
            break;
            
        case 'compile':
            $automation->compile();
            break;
            
        case 'validate':
            $valid = $automation->validate();
            exit($valid ? 0 : 1);
            break;
            
        case 'full':
            $valid = $automation->full();
            exit($valid ? 0 : 1);
            break;
            
        default:
            echo "Unknown command: $command\n";
            echo "Run 'php german-translation-automation.php help' for usage information.\n";
            exit(1);
    }
    
    exit(0);
}