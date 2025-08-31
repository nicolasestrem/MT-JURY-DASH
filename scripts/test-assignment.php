<?php
/**
 * Test assignment functionality
 */

// Load WordPress
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Get services
$assignment_repo = mt_assignment_repository();
$assignment_service = mt_assignment_service();

echo "Testing Mobility Trailblazers Assignment System\n";
echo "================================================\n\n";

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

echo "Test 1: Create Assignment\n";
echo "Jury Member: {$jury_members[0]->post_title} (ID: $jury_id)\n";
echo "Candidate: {$candidates[0]->post_title} (ID: $candidate_id)\n";

// Check if already exists
if ($assignment_repo->exists($jury_id, $candidate_id)) {
    echo "Assignment already exists, deleting first...\n";
    $assignment_repo->delete_assignment($jury_id, $candidate_id);
}

// Test create assignment
$result = $assignment_repo->create_assignment($jury_id, $candidate_id, get_current_user_id());

if ($result) {
    echo "✓ Assignment created successfully! ID: $result\n\n";
} else {
    echo "✗ Assignment creation failed!\n";
    echo "Last error: " . $assignment_repo->get_last_error() . "\n\n";
}

// Test get assignments
echo "Test 2: Get Assignments\n";
$assignments = $assignment_repo->get_by_jury_member($jury_id);
echo "Found " . count($assignments) . " assignment(s) for this jury member\n";

if (!empty($assignments)) {
    foreach ($assignments as $assignment) {
        $candidate = get_post($assignment->candidate_id);
        echo "  - " . ($candidate ? $candidate->post_title : 'Unknown') . 
             " (assigned: {$assignment->assigned_at})\n";
    }
}

echo "\nTest 3: Assignment Statistics\n";
$stats = $assignment_repo->get_statistics();
echo "Total assignments: " . ($stats['total_assignments'] ?? 0) . "\n";
echo "Unique jury members: " . ($stats['unique_jury_members'] ?? 0) . "\n";
echo "Unique candidates: " . ($stats['unique_candidates'] ?? 0) . "\n";

echo "\n✓ All tests completed!\n";