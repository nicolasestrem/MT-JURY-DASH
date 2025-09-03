<?php
/**
 * WordPress PO to MO Compiler
 * Uses WordPress built-in functions to compile .po to .mo
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Include necessary files
require_once(ABSPATH . 'wp-admin/includes/translation-install.php');

$po_file = './Plugin/languages/mobility-trailblazers-de_DE.po';
$mo_file = './Plugin/languages/mobility-trailblazers-de_DE.mo';

echo "Compiling German translations for Mobility Trailblazers...\n";

// Check if PO file exists
if (!file_exists($po_file)) {
    die("Error: PO file not found at $po_file\n");
}

// Use WordPress's MO writer if available
if (class_exists('MO')) {
    $mo = new MO();
    
    // Import from PO file
    if (function_exists('import_from_file')) {
        $mo->import_from_file($po_file);
        
        // Export to MO file
        if ($mo->export_to_file($mo_file)) {
            echo "Successfully compiled $mo_file\n";
            echo "File size: " . filesize($mo_file) . " bytes\n";
        } else {
            echo "Error: Failed to export MO file\n";
        }
    }
} else {
    echo "WordPress MO class not available. Using fallback method...\n";
    
    // Fallback: Simple binary compilation
    $translations = [];
    $po_content = file_get_contents($po_file);
    
    // Parse PO file
    preg_match_all('/msgid\s+"(.*)"\s*\nmsgstr\s+"(.*)"/U', $po_content, $matches);
    
    for ($i = 0; $i < count($matches[0]); $i++) {
        if (!empty($matches[1][$i])) {
            $translations[$matches[1][$i]] = $matches[2][$i];
        }
    }
    
    echo "Parsed " . count($translations) . " translations\n";
    
    // Note: This would need proper MO binary format implementation
    // For now, we'll just indicate that manual compilation is needed
    echo "Please use a tool like Poedit or msgfmt to compile the MO file\n";
}

echo "\nTranslation Summary:\n";
echo "- Source: $po_file\n";
echo "- Target: $mo_file\n";
echo "- Locale: de_DE (German)\n";
echo "- Text Domain: mobility-trailblazers\n";