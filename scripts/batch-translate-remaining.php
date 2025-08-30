#!/usr/bin/env php
<?php
/**
 * Batch Translation Script for Remaining German Strings
 * 
 * This script provides a framework for batch translating the remaining
 * untranslated strings using various methods:
 * - Manual translation templates
 * - DeepL API integration (when available)
 * - Google Translate API (when available)
 * - Pattern-based translations for common UI elements
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

class MT_Batch_Translator {
    
    private $common_patterns = [];
    private $stats = [
        'processed' => 0,
        'translated' => 0,
        'skipped' => 0
    ];
    
    public function __construct() {
        $this->initializePatterns();
    }
    
    /**
     * Initialize common translation patterns
     */
    private function initializePatterns() {
        // Common UI patterns
        $this->common_patterns = [
            // Actions
            '/^Add (.+)$/' => 'Hinzufügen: $1',
            '/^Edit (.+)$/' => 'Bearbeiten: $1',
            '/^Delete (.+)$/' => 'Löschen: $1',
            '/^View (.+)$/' => 'Anzeigen: $1',
            '/^Update (.+)$/' => 'Aktualisieren: $1',
            '/^Save (.+)$/' => 'Speichern: $1',
            '/^Create (.+)$/' => 'Erstellen: $1',
            '/^Remove (.+)$/' => 'Entfernen: $1',
            '/^Select (.+)$/' => 'Auswählen: $1',
            '/^Export (.+)$/' => 'Exportieren: $1',
            '/^Import (.+)$/' => 'Importieren: $1',
            '/^Download (.+)$/' => 'Herunterladen: $1',
            '/^Upload (.+)$/' => 'Hochladen: $1',
            
            // Status messages
            '/^(.+) successfully$/' => '$1 erfolgreich',
            '/^(.+) failed$/' => '$1 fehlgeschlagen',
            '/^(.+) completed$/' => '$1 abgeschlossen',
            '/^(.+) saved$/' => '$1 gespeichert',
            '/^(.+) deleted$/' => '$1 gelöscht',
            '/^(.+) updated$/' => '$1 aktualisiert',
            '/^(.+) created$/' => '$1 erstellt',
            '/^(.+) added$/' => '$1 hinzugefügt',
            '/^(.+) removed$/' => '$1 entfernt',
            
            // Questions
            '/^Are you sure you want to (.+)\?$/' => 'Sind Sie sicher, dass Sie $1 möchten?',
            '/^Do you want to (.+)\?$/' => 'Möchten Sie $1?',
            '/^Would you like to (.+)\?$/' => 'Möchten Sie $1?',
            
            // Error messages
            '/^Invalid (.+)$/' => 'Ungültige(r/s) $1',
            '/^Missing (.+)$/' => 'Fehlende(r/s) $1',
            '/^Required (.+)$/' => 'Erforderliche(r/s) $1',
            '/^Failed to (.+)$/' => 'Fehler beim $1',
            '/^Unable to (.+)$/' => 'Kann nicht $1',
            '/^Cannot (.+)$/' => 'Kann nicht $1',
            '/^Error: (.+)$/' => 'Fehler: $1',
            
            // Form labels
            '/^(.+) Name$/' => '$1-Name',
            '/^(.+) Email$/' => '$1-E-Mail',
            '/^(.+) Address$/' => '$1-Adresse',
            '/^(.+) Phone$/' => '$1-Telefon',
            '/^(.+) Date$/' => '$1-Datum',
            '/^(.+) Time$/' => '$1-Zeit',
            '/^(.+) Description$/' => '$1-Beschreibung',
            '/^(.+) Title$/' => '$1-Titel',
            '/^(.+) Status$/' => '$1-Status',
            '/^(.+) Type$/' => '$1-Typ',
            '/^(.+) Category$/' => '$1-Kategorie',
            '/^(.+) Settings$/' => '$1-Einstellungen',
            
            // Plurals
            '/^(\d+) items?$/' => '$1 Element(e)',
            '/^(\d+) records?$/' => '$1 Datensatz/Datensätze',
            '/^(\d+) entries?$/' => '$1 Eintrag/Einträge',
            '/^(\d+) results?$/' => '$1 Ergebnis(se)',
            '/^(\d+) candidates?$/' => '$1 Kandidat(en)',
            '/^(\d+) evaluations?$/' => '$1 Bewertung(en)',
            '/^(\d+) assignments?$/' => '$1 Zuweisung(en)',
            
            // Navigation
            '/^Back to (.+)$/' => 'Zurück zu $1',
            '/^Go to (.+)$/' => 'Gehe zu $1',
            '/^Return to (.+)$/' => 'Zurück zu $1',
            '/^Continue to (.+)$/' => 'Weiter zu $1',
            
            // Confirmations
            '/^Confirm (.+)$/' => 'Bestätigen: $1',
            '/^Cancel (.+)$/' => 'Abbrechen: $1',
            '/^Approve (.+)$/' => 'Genehmigen: $1',
            '/^Reject (.+)$/' => 'Ablehnen: $1',
            
            // Loading states
            '/^Loading (.+)$/' => 'Lade $1',
            '/^Saving (.+)$/' => 'Speichere $1',
            '/^Processing (.+)$/' => 'Verarbeite $1',
            '/^Updating (.+)$/' => 'Aktualisiere $1',
            '/^Deleting (.+)$/' => 'Lösche $1',
            
            // Validation
            '/^Please enter (.+)$/' => 'Bitte geben Sie $1 ein',
            '/^Please select (.+)$/' => 'Bitte wählen Sie $1',
            '/^Please choose (.+)$/' => 'Bitte wählen Sie $1',
            '/^Please provide (.+)$/' => 'Bitte geben Sie $1 an',
            
            // Common words
            '/\bSettings\b/' => 'Einstellungen',
            '/\bDashboard\b/' => 'Dashboard',
            '/\bProfile\b/' => 'Profil',
            '/\bUser\b/' => 'Benutzer',
            '/\bAdmin\b/' => 'Administrator',
            '/\bManage\b/' => 'Verwalten',
            '/\bOptions\b/' => 'Optionen',
            '/\bPreferences\b/' => 'Einstellungen',
            '/\bConfiguration\b/' => 'Konfiguration',
            '/\bNotifications\b/' => 'Benachrichtigungen'
        ];
    }
    
    /**
     * Apply pattern-based translations
     */
    public function applyPatternTranslation($text) {
        foreach ($this->common_patterns as $pattern => $replacement) {
            if (preg_match($pattern, $text, $matches)) {
                // Handle replacements with captured groups
                $translation = $replacement;
                for ($i = 1; $i < count($matches); $i++) {
                    $translation = str_replace('$' . $i, $matches[$i], $translation);
                }
                return $translation;
            }
        }
        return null;
    }
    
    /**
     * Process admin strings with context-aware translations
     */
    public function translateAdminStrings($strings) {
        $translations = [];
        
        // Common admin interface translations
        $admin_dictionary = [
            'Dashboard' => 'Dashboard',
            'Settings' => 'Einstellungen',
            'Tools' => 'Werkzeuge',
            'Users' => 'Benutzer',
            'Plugins' => 'Plugins',
            'Media' => 'Medien',
            'Pages' => 'Seiten',
            'Posts' => 'Beiträge',
            'Comments' => 'Kommentare',
            'Appearance' => 'Design',
            'Widgets' => 'Widgets',
            'Menus' => 'Menüs',
            'General' => 'Allgemein',
            'Reading' => 'Lesen',
            'Writing' => 'Schreiben',
            'Discussion' => 'Diskussion',
            'Permalinks' => 'Permalinks',
            'Privacy' => 'Datenschutz',
            'All' => 'Alle',
            'Published' => 'Veröffentlicht',
            'Draft' => 'Entwurf',
            'Pending' => 'Ausstehend',
            'Private' => 'Privat',
            'Trash' => 'Papierkorb',
            'Bulk Actions' => 'Massenaktionen',
            'Apply' => 'Anwenden',
            'Filter' => 'Filtern',
            'Search' => 'Suchen',
            'Screen Options' => 'Ansicht anpassen',
            'Help' => 'Hilfe'
        ];
        
        foreach ($strings as $msgid => $entry) {
            // Check direct dictionary match
            if (isset($admin_dictionary[$msgid])) {
                $translations[$msgid] = $admin_dictionary[$msgid];
                $this->stats['translated']++;
            }
            // Try pattern matching
            elseif ($pattern_translation = $this->applyPatternTranslation($msgid)) {
                $translations[$msgid] = $pattern_translation;
                $this->stats['translated']++;
            }
            else {
                $this->stats['skipped']++;
            }
            $this->stats['processed']++;
        }
        
        return $translations;
    }
    
    /**
     * Generate translation template for manual completion
     */
    public function generateManualTemplate($category, $strings, $output_file) {
        $content = "MOBILITY TRAILBLAZERS - GERMAN TRANSLATION TEMPLATE\n";
        $content .= "Category: " . strtoupper($category) . "\n";
        $content .= "Total Strings: " . count($strings) . "\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat('=', 60) . "\n\n";
        
        $content .= "INSTRUCTIONS:\n";
        $content .= "1. Translate each English string to German\n";
        $content .= "2. Use formal 'Sie' form consistently\n";
        $content .= "3. Maintain placeholders like %s, %d, %1\$s\n";
        $content .= "4. Preserve HTML tags if present\n";
        $content .= "5. Keep translations concise for UI elements\n\n";
        
        $content .= str_repeat('=', 60) . "\n\n";
        
        $index = 1;
        foreach ($strings as $msgid => $entry) {
            $content .= "[$index]\n";
            $content .= "English: $msgid\n";
            $content .= "German:  [TRANSLATE]\n";
            
            // Add context if available
            if (!empty($entry['references'])) {
                $refs = array_slice($entry['references'], 0, 2);
                $content .= "Used in: " . implode(', ', $refs) . "\n";
            }
            
            // Add pattern suggestion if available
            $suggestion = $this->applyPatternTranslation($msgid);
            if ($suggestion) {
                $content .= "Suggestion: $suggestion\n";
            }
            
            $content .= "\n";
            $index++;
            
            // Add section breaks every 25 items
            if ($index % 25 == 0) {
                $content .= str_repeat('-', 40) . "\n";
                $content .= "Progress: $index / " . count($strings) . "\n";
                $content .= str_repeat('-', 40) . "\n\n";
            }
        }
        
        file_put_contents($output_file, $content);
        echo "✓ Template generated: $output_file\n";
        echo "  Strings to translate: " . count($strings) . "\n";
    }
    
    /**
     * Run batch translation process
     */
    public function run() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "BATCH TRANSLATION PROCESSOR\n";
        echo str_repeat('=', 60) . "\n\n";
        
        // Load untranslated strings from latest export
        $exports = glob(MT_EXPORT_DIR . '/untranslated_strings_*.json');
        if (empty($exports)) {
            echo "No untranslated strings export found.\n";
            echo "Run 'php german-translation-automation.php extract' first.\n";
            return;
        }
        
        // Use latest export
        sort($exports);
        $latest_export = end($exports);
        $data = json_decode(file_get_contents($latest_export), true);
        
        echo "Loaded: " . basename($latest_export) . "\n";
        echo "Categories:\n";
        foreach ($data['metadata']['categories'] as $cat => $count) {
            echo "  - $cat: $count strings\n";
        }
        echo "\n";
        
        // Process each category
        $all_translations = [];
        
        // Process admin strings with pattern matching
        if (!empty($data['strings']['admin'])) {
            echo "Processing admin strings with pattern matching...\n";
            $admin_translations = $this->translateAdminStrings($data['strings']['admin']);
            $all_translations = array_merge($all_translations, $admin_translations);
            
            // Generate template for remaining admin strings
            $remaining_admin = array_diff_key($data['strings']['admin'], $admin_translations);
            if (!empty($remaining_admin)) {
                $template_file = MT_TRANS_DIR . '/admin-manual-' . date('Ymd-His') . '.txt';
                $this->generateManualTemplate('admin', $remaining_admin, $template_file);
            }
        }
        
        // Generate templates for other categories
        if (!empty($data['strings']['other'])) {
            $template_file = MT_TRANS_DIR . '/other-manual-' . date('Ymd-His') . '.txt';
            $this->generateManualTemplate('other', $data['strings']['other'], $template_file);
        }
        
        // Save pattern-matched translations
        if (!empty($all_translations)) {
            $output_file = MT_TRANS_DIR . '/pattern-translations-' . date('Ymd-His') . '.json';
            file_put_contents($output_file, json_encode([
                'metadata' => [
                    'method' => 'pattern_matching',
                    'created' => date('Y-m-d H:i:s'),
                    'total' => count($all_translations)
                ],
                'translations' => $all_translations
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            echo "\n✓ Pattern translations saved: $output_file\n";
        }
        
        // Summary
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "BATCH TRANSLATION SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        echo "Processed: " . $this->stats['processed'] . " strings\n";
        echo "Translated: " . $this->stats['translated'] . " (pattern matching)\n";
        echo "Skipped: " . $this->stats['skipped'] . " (need manual translation)\n";
        echo "\nNext Steps:\n";
        echo "1. Review pattern-matched translations in translations/ directory\n";
        echo "2. Complete manual translations in generated templates\n";
        echo "3. Run import script to apply translations\n";
        echo "\nTo import translations:\n";
        echo "  php scripts/import-german-translations.php\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $translator = new MT_Batch_Translator();
    $translator->run();
}