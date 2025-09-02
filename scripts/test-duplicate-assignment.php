<?php
/**
 * Test duplicate assignment handling
 */

// Load WordPress
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Set up as admin user
wp_set_current_user(1);

echo "Testing Duplicate Assignment Handling\n";
echo "=====================================\n\n";

// Get services
$assignment_repo = mt_assignment_repository();

// Get first jury member and candidate
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

$candidates = get_posts([
    'post_type' => 'mt_candidate', 
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (empty($jury_members) || empty($candidates)) {
    echo "ERROR: No jury members or candidates found!\n";
    exit(1);
}

$jury_id = $jury_members[0]->ID;
$candidate_id = $candidates[0]->ID;

echo "Test Parameters:\n";
echo "  Jury Member: {$jury_members[0]->post_title} (ID: $jury_id)\n";
echo "  Candidate: {$candidates[0]->post_title} (ID: $candidate_id)\n\n";

// Test 1: First assignment (should succeed)
echo "Test 1: Creating initial assignment...\n";
$result1 = $assignment_repo->create_assignment($jury_id, $candidate_id, get_current_user_id());
if ($result1) {
    echo "✓ First assignment created successfully! ID: $result1\n\n";
} else {
    echo "⚠ Assignment already exists or failed\n\n";
}

// Test 2: Duplicate assignment (should fail gracefully)
echo "Test 2: Attempting duplicate assignment...\n";
$result2 = $assignment_repo->create_assignment($jury_id, $candidate_id, get_current_user_id());
if ($result2) {
    echo "✗ ERROR: Duplicate assignment was created! This should not happen.\n";
} else {
    echo "✓ Duplicate assignment prevented successfully\n";
}

// Test 3: Check via AJAX endpoint
echo "\nTest 3: Testing via AJAX endpoint...\n";
$_POST = [
    'action' => 'mt_manual_assign',
    'nonce' => wp_create_nonce('mt_admin_nonce'),
    'jury_member_id' => $jury_id,
    'candidate_ids' => [$candidate_id]
];
$_REQUEST = $_POST;

ob_start();
$ajax_handler = new \MobilityTrailblazers\Ajax\MT_Assignment_Ajax();
$ajax_handler->manual_assign();
$output = ob_get_clean();

$response = json_decode($output, true);
if ($response && $response['success']) {
    if (strpos($response['data']['data']['message'], 'already assigned') !== false) {
        echo "✓ AJAX endpoint correctly handled duplicate\n";
    } else {
        echo "Response: " . $response['data']['data']['message'] . "\n";
    }
} else {
    echo "AJAX Response: " . ($response ? json_encode($response) : $output) . "\n";
}

// Clean up - remove the test assignment
echo "\nCleaning up test assignment...\n";
$assignment_repo->delete_assignment($jury_id, $candidate_id);
echo "✓ Test assignment removed\n";

echo "\n✅ All tests completed!\n";