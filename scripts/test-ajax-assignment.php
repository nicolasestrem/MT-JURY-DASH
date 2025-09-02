<?php
/**
 * Test AJAX assignment endpoint
 */

// Load WordPress
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Set up as admin user
wp_set_current_user(1);

echo "Testing AJAX Assignment Endpoint\n";
echo "=================================\n\n";

// Get a jury member and candidate
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

$candidates = get_posts([
    'post_type' => 'mt_candidate', 
    'posts_per_page' => 2,
    'post_status' => 'publish',
    'orderby' => 'rand'
]);

if (empty($jury_members) || empty($candidates)) {
    echo "ERROR: No jury members or candidates found!\n";
    exit(1);
}

$jury_id = $jury_members[0]->ID;
$candidate_ids = wp_list_pluck($candidates, 'ID');

echo "Test Parameters:\n";
echo "  Jury Member: {$jury_members[0]->post_title} (ID: $jury_id)\n";
echo "  Candidates: " . implode(', ', $candidate_ids) . "\n\n";

// Simulate POST data
$_POST = [
    'action' => 'mt_manual_assign',
    'nonce' => wp_create_nonce('mt_admin_nonce'),
    'jury_member_id' => $jury_id,
    'candidate_ids' => $candidate_ids
];

// Also set REQUEST for nonce verification
$_REQUEST = $_POST;

// Capture the AJAX response
ob_start();
$ajax_handler = new \MobilityTrailblazers\Ajax\MT_Assignment_Ajax();

try {
    $ajax_handler->manual_assign();
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
    
    if (isset($response['data'])) {
        if (isset($response['data']['message'])) {
            echo "  Message: " . $response['data']['message'] . "\n";
        }
        if (isset($response['data']['created'])) {
            echo "  Created: " . $response['data']['created'] . "\n";
        }
        if (isset($response['data']['error'])) {
            echo "  Error: " . $response['data']['error'] . "\n";
        }
    }
} else {
    echo "Raw Output:\n$output\n";
}

echo "\n✓ Test completed!\n";