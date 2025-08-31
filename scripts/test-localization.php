<?php
/**
 * Test German Localization
 * 
 * Verifies that German translations are properly loaded and accessible
 * 
 * Usage: php scripts/test-localization.php
 * 
 * @package MobilityTrailblazers
 * @since 2.5.33
 */

// Load WordPress
$wp_load = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die("Error: WordPress not found. Please run this script from the plugin directory.\n");
}

require_once $wp_load;

// Colors for output
$green = "\033[0;32m";
$yellow = "\033[1;33m";
$red = "\033[0;31m";
$reset = "\033[0m";

echo "${green}=== Mobility Trailblazers Localization Test ===${reset}\n";
echo "Plugin Version: 2.5.33\n\n";

// Check if German locale is active
$current_locale = get_locale();
echo "Current WordPress Locale: ${yellow}$current_locale${reset}\n\n";

// Load plugin text domain
$domain = 'mobility-trailblazers';
$loaded = load_plugin_textdomain($domain, false, 'mobility-trailblazers/languages/');
echo "Text domain loaded: " . ($loaded ? "${green}YES${reset}" : "${red}NO${reset}") . "\n\n";

// Test key translations
$test_strings = [
    'Evaluation submitted successfully!' => 'Bewertung erfolgreich eingereicht!',
    'Loading...' => 'Wird geladen...',
    'Submit' => 'Absenden',
    'Cancel' => 'Abbrechen',
    'Save' => 'Speichern',
    'Draft saved successfully!' => 'Entwurf erfolgreich gespeichert!',
    'Please rate all criteria before submitting.' => 'Bitte bewerten Sie alle Kriterien vor dem Absenden.',
    'Back to Dashboard' => 'Zurück zum Dashboard',
    'Evaluate Candidate' => 'Kandidat bewerten',
    'Total Score:' => 'Gesamtpunktzahl:',
    'Completed' => 'Abgeschlossen',
    'Pending' => 'Ausstehend',
    'Search candidates...' => 'Kandidaten suchen...',
    'No results found.' => 'Keine Ergebnisse gefunden.',
    'You must be logged in as a jury member to access this dashboard.' => 'Sie müssen als Jurymitglied angemeldet sein, um auf dieses Dashboard zugreifen zu können.'
];

echo "${yellow}Testing Critical Translations:${reset}\n";
echo str_repeat('-', 80) . "\n";

$passed = 0;
$failed = 0;

// Test each string
foreach ($test_strings as $english => $expected_german) {
    $translated = __($english, $domain);
    
    // Check if translation matches expected (when in German locale)
    if ($current_locale === 'de_DE' || $current_locale === 'de_DE_formal') {
        if ($translated === $expected_german) {
            echo "${green}✓${reset} '$english'\n";
            echo "   → '$translated'\n";
            $passed++;
        } else {
            echo "${red}✗${reset} '$english'\n";
            echo "   Expected: '$expected_german'\n";
            echo "   Got: '$translated'\n";
            $failed++;
        }
    } else {
        // In English locale, just show what would be translated
        echo "${yellow}?${reset} '$english'\n";
        echo "   → Would translate to: '$expected_german' (in German locale)\n";
        echo "   Current: '$translated'\n";
    }
    echo "\n";
}

// Summary
echo str_repeat('=', 80) . "\n";
echo "${green}Summary:${reset}\n";

if ($current_locale === 'de_DE' || $current_locale === 'de_DE_formal') {
    echo "Passed: ${green}$passed${reset}\n";
    echo "Failed: ${red}$failed${reset}\n";
    
    if ($failed === 0) {
        echo "\n${green}✅ All translations working correctly!${reset}\n";
    } else {
        echo "\n${red}⚠️  Some translations are not working properly.${reset}\n";
        echo "Please check:\n";
        echo "1. The .mo file is compiled correctly\n";
        echo "2. The plugin text domain is loaded\n";
        echo "3. The translations exist in the .po file\n";
    }
} else {
    echo "${yellow}Note:${reset} WordPress is currently in English locale ($current_locale).\n";
    echo "To test German translations, change WordPress locale to 'de_DE' in Settings → General.\n";
}

// Check if .mo file exists
$mo_file = dirname(__DIR__) . '/languages/mobility-trailblazers-de_DE.mo';
echo "\n${yellow}File Check:${reset}\n";
echo ".mo file exists: " . (file_exists($mo_file) ? "${green}YES${reset}" : "${red}NO${reset}") . "\n";
if (file_exists($mo_file)) {
    echo ".mo file size: " . number_format(filesize($mo_file)) . " bytes\n";
    echo ".mo file modified: " . date('Y-m-d H:i:s', filemtime($mo_file)) . "\n";
}