<?php
/**
 * Script to find missing German translations
 */

$pot_file = __DIR__ . '/../languages/mobility-trailblazers.pot';
$po_file = __DIR__ . '/../languages/mobility-trailblazers-de_DE.po';

// Parse POT file
function parse_po_file($file) {
    $strings = [];
    $content = file_get_contents($file);
    
    // Split by double newlines to get entries
    $entries = preg_split('/\n\n+/', $content);
    
    foreach ($entries as $entry) {
        if (preg_match('/msgid\s+"(.+?)"/s', $entry, $id_match)) {
            $msgid = $id_match[1];
            if ($msgid === '') continue; // Skip empty msgid
            
            $msgstr = '';
            if (preg_match('/msgstr\s+"(.*)"/s', $entry, $str_match)) {
                $msgstr = $str_match[1];
            }
            
            // Handle multiline strings
            if (preg_match_all('/^"(.+?)"$/m', $entry, $multiline)) {
                $full_id = '';
                $full_str = '';
                $in_msgstr = false;
                
                foreach ($multiline[0] as $line) {
                    $clean_line = trim($line, '"');
                    if (strpos($line, 'msgid') !== false) {
                        continue;
                    }
                    if (strpos($line, 'msgstr') !== false) {
                        $in_msgstr = true;
                        continue;
                    }
                    
                    if ($in_msgstr) {
                        $full_str .= $clean_line;
                    }
                }
            }
            
            $strings[$msgid] = $msgstr;
        }
    }
    
    return $strings;
}

echo "Analyzing translation files...\n";
echo "================================\n\n";

$pot_strings = parse_po_file($pot_file);
$po_strings = parse_po_file($po_file);

$missing = [];
$empty = [];

foreach ($pot_strings as $msgid => $msgstr) {
    if (!isset($po_strings[$msgid])) {
        $missing[] = $msgid;
    } elseif (empty($po_strings[$msgid])) {
        $empty[] = $msgid;
    }
}

echo "Total strings in POT file: " . count($pot_strings) . "\n";
echo "Total strings in PO file: " . count($po_strings) . "\n";
echo "Missing translations: " . count($missing) . "\n";
echo "Empty translations: " . count($empty) . "\n\n";

if (count($missing) > 0) {
    echo "First 50 missing strings:\n";
    echo "-------------------------\n";
    $count = 0;
    foreach ($missing as $str) {
        $count++;
        if ($count > 50) break;
        echo $count . ". " . $str . "\n";
    }
}

echo "\nTranslation completion: " . round((count($po_strings) - count($missing) - count($empty)) / count($pot_strings) * 100, 1) . "%\n";