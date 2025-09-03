<?php
/**
 * Simple PO to MO compiler for WordPress translations
 * 
 * This script compiles .po files to .mo binary format
 */

// Check if file is provided
if ($argc < 2) {
    echo "Usage: php compile-mo.php <po-file>\n";
    exit(1);
}

$po_file = $argv[1];

if (!file_exists($po_file)) {
    echo "Error: File $po_file not found\n";
    exit(1);
}

// Generate .mo filename
$mo_file = str_replace('.po', '.mo', $po_file);

echo "Compiling $po_file to $mo_file...\n";

// Simple PO to MO converter (basic implementation)
class PoToMoConverter {
    private $entries = [];
    
    public function parse_po($filename) {
        $content = file_get_contents($filename);
        $lines = explode("\n", $content);
        
        $msgid = '';
        $msgstr = '';
        $in_msgid = false;
        $in_msgstr = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Handle msgid
            if (strpos($line, 'msgid "') === 0) {
                if ($msgid && $msgstr) {
                    $this->entries[$msgid] = $msgstr;
                }
                $msgid = substr($line, 7, -1);
                $msgstr = '';
                $in_msgid = true;
                $in_msgstr = false;
            }
            // Handle msgstr
            elseif (strpos($line, 'msgstr "') === 0) {
                $msgstr = substr($line, 8, -1);
                $in_msgid = false;
                $in_msgstr = true;
            }
            // Handle continued strings
            elseif ($line[0] === '"') {
                $content = substr($line, 1, -1);
                if ($in_msgid) {
                    $msgid .= $content;
                } elseif ($in_msgstr) {
                    $msgstr .= $content;
                }
            }
        }
        
        // Add last entry
        if ($msgid && $msgstr) {
            $this->entries[$msgid] = $msgstr;
        }
    }
    
    public function write_mo($filename) {
        // MO file format header
        $header = pack('L', 0x950412de); // Magic number
        $header .= pack('L', 0); // File format revision
        $header .= pack('L', count($this->entries)); // Number of strings
        $header .= pack('L', 28); // Offset of table with original strings
        $header .= pack('L', 28 + count($this->entries) * 8); // Offset of table with translations
        $header .= pack('L', 0); // Size of hashing table
        $header .= pack('L', 28 + count($this->entries) * 16); // Offset of hashing table
        
        $original_table = '';
        $translation_table = '';
        $original_strings = '';
        $translation_strings = '';
        
        $current_offset = 28 + count($this->entries) * 16;
        
        foreach ($this->entries as $original => $translation) {
            // Original string entry
            $original_length = strlen($original);
            $original_table .= pack('L', $original_length);
            $original_table .= pack('L', $current_offset);
            $original_strings .= $original . "\0";
            $current_offset += $original_length + 1;
            
            // Translation string entry
            $translation_length = strlen($translation);
            $translation_table .= pack('L', $translation_length);
            $translation_table .= pack('L', $current_offset);
            $translation_strings .= $translation . "\0";
            $current_offset += $translation_length + 1;
        }
        
        // Write MO file
        $mo_content = $header . $original_table . $translation_table . $original_strings . $translation_strings;
        
        if (file_put_contents($filename, $mo_content) === false) {
            return false;
        }
        
        return true;
    }
}

// Convert the file
$converter = new PoToMoConverter();
$converter->parse_po($po_file);

if ($converter->write_mo($mo_file)) {
    echo "Successfully compiled to $mo_file\n";
    echo "File size: " . filesize($mo_file) . " bytes\n";
} else {
    echo "Error: Failed to write $mo_file\n";
    exit(1);
}