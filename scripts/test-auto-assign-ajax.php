<?php
/**
 * Test auto-assignment AJAX endpoint
 */

// Load WordPress
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Set up as admin user
wp_set_current_user(1);

echo "Testing Auto-Assignment AJAX Endpoint\n";
echo "=====================================\n\n";

// Simulate POST data for auto-assignment
$_POST = [
    'action' => 'mt_auto_assign',
    'nonce' => wp_create_nonce('mt_admin_nonce'),
    'candidates_per_jury' => 2,
    'clear_existing' => 'false'
];

// Also set REQUEST for nonce verification
$_REQUEST = $_POST;

// Capture the AJAX response
ob_start();
$ajax_handler = new \MobilityTrailblazers\Ajax\MT_Assignment_Ajax();

try {
    $ajax_handler->auto_assign();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

// Decode the JSON response
$response = json_decode($output, true);

if ($response) {
    echo "AJAX Response:\n";
    echo "  Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
    
    if ($response['success']) {
        if (isset($response['data']['created'])) {
            echo "  Created: " . $response['data']['created'] . " assignments\n";
        }
        if (isset($response['data']['skipped'])) {
            echo "  Skipped: " . $response['data']['skipped'] . " existing\n";
        }
        if (isset($response['data']['message'])) {
            echo "  Message: " . $response['data']['message'] . "\n";
        }
    } else {
        if (isset($response['data']['message'])) {
            echo "  Error: " . $response['data']['message'] . "\n";
        }
        if (isset($response['data']['data']['error_id'])) {
            echo "  Error ID: " . $response['data']['data']['error_id'] . "\n";
        }
    }
} else {
    echo "Raw Output:\n$output\n";
}

echo "\n✓ Test completed!\n";