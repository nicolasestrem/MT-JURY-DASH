<?php
/**
 * Compile MO file from PO file
 * This script compiles the German PO file into a binary MO file for WordPress
 */

// Define file paths
$po_file = __DIR__ . '/../languages/mobility-trailblazers-de_DE.po';
$mo_file = __DIR__ . '/../languages/mobility-trailblazers-de_DE.mo';

// Check if PO file exists
if (!file_exists($po_file)) {
    die("Error: PO file not found at: {$po_file}\n");
}

// Try to use msgfmt if available
$msgfmt_available = false;
exec('msgfmt --version 2>&1', $output, $return_code);
if ($return_code === 0) {
    $msgfmt_available = true;
}

if ($msgfmt_available) {
    // Use msgfmt for compilation
    $command = sprintf('msgfmt -o %s %s 2>&1', 
        escapeshellarg($mo_file), 
        escapeshellarg($po_file)
    );
    
    exec($command, $output, $return_code);
    
    if ($return_code === 0) {
        echo "✓ MO file compiled successfully using msgfmt\n";
        echo "  Output: {$mo_file}\n";
        
        // Show file size
        $size = filesize($mo_file);
        echo "  Size: " . number_format($size) . " bytes\n";
    } else {
        echo "Error compiling with msgfmt:\n";
        echo implode("\n", $output) . "\n";
        exit(1);
    }
} else {
    // Fallback: Use PHP to compile (basic implementation)
    echo "msgfmt not available, using PHP fallback...\n";
    
    // Parse PO file
    $po_content = file_get_contents($po_file);
    
    // Count translations
    preg_match_all('/^msgid\s+"(.*)"/m', $po_content, $msgids);
    preg_match_all('/^msgstr\s+"(.*)"/m', $po_content, $msgstrs);
    
    $translation_count = 0;
    foreach ($msgstrs[1] as $msgstr) {
        if (!empty($msgstr)) {
            $translation_count++;
        }
    }
    
    echo "Note: PHP fallback creates a placeholder MO file.\n";
    echo "For production, please install gettext tools and run:\n";
    echo "  msgfmt -o {$mo_file} {$po_file}\n\n";
    
    // Create a basic MO file structure (simplified)
    $mo_data = pack('L', 0x950412de); // Magic number
    $mo_data .= pack('L', 0); // Version
    $mo_data .= pack('L', 0); // Number of strings (placeholder)
    $mo_data .= pack('L', 28); // Offset of table with original strings
    $mo_data .= pack('L', 28); // Offset of table with translation strings
    $mo_data .= pack('L', 0); // Size of hashing table
    $mo_data .= pack('L', 28); // Offset of hashing table
    
    // Write basic MO file
    file_put_contents($mo_file, $mo_data);
    
    echo "✓ Basic MO file created (placeholder)\n";
    echo "  Found {$translation_count} translations in PO file\n";
    echo "  Output: {$mo_file}\n";
}

// Verify MO file was created
if (file_exists($mo_file)) {
    $stats = stat($mo_file);
    echo "\nMO file details:\n";
    echo "  Modified: " . date('Y-m-d H:i:s', $stats['mtime']) . "\n";
    echo "  Permissions: " . substr(sprintf('%o', fileperms($mo_file)), -4) . "\n";
} else {
    echo "Error: MO file was not created\n";
    exit(1);
}