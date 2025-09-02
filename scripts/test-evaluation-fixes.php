<?php
/**
 * Test script to verify evaluation fixes
 * Run with: php scripts/test-evaluation-fixes.php
 */

// Load WordPress
require_once '/var/www/html/wp-load.php';

// Colors for output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$reset = "\033[0m";

echo "\n{$yellow}Testing Evaluation System Fixes{$reset}\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test 1: Check if default values are 5.0
echo "Test 1: Default values should be 5.0\n";
$evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
// Get a real candidate ID
$candidates = get_posts(['post_type' => 'mt_candidate', 'posts_per_page' => 1]);
$candidate_id = !empty($candidates) ? $candidates[0]->ID : 1;

// Get a real jury member ID
$jury_members = get_posts(['post_type' => 'mt_jury_member', 'posts_per_page' => 1]);
$jury_id = !empty($jury_members) ? $jury_members[0]->ID : 1;

$test_data = [
    'jury_member_id' => $jury_id,
    'candidate_id' => $candidate_id,
    'status' => 'draft'
];

$reflection = new ReflectionClass($evaluation_service);
$method = $reflection->getMethod('save_evaluation');
$result = $method->invoke($evaluation_service, $test_data);

if (!is_wp_error($result)) {
    // Get the created evaluation
    $repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
    $eval = $repo->find($result);
    
    $scores = [
        'courage_score' => $eval->courage_score ?? $eval->criterion_1 ?? null,
        'innovation_score' => $eval->innovation_score ?? $eval->criterion_2 ?? null,
        'implementation_score' => $eval->implementation_score ?? $eval->criterion_3 ?? null,
        'relevance_score' => $eval->relevance_score ?? $eval->criterion_4 ?? null,
        'visibility_score' => $eval->visibility_score ?? $eval->criterion_5 ?? null,
    ];
    
    $all_five = true;
    foreach ($scores as $field => $score) {
        if ($score != 5.0) {
            echo "{$red}✗ $field = $score (expected 5.0){$reset}\n";
            $all_five = false;
        } else {
            echo "{$green}✓ $field = 5.0{$reset}\n";
        }
    }
    
    if ($all_five) {
        echo "{$green}✓ Test 1 PASSED: All defaults are 5.0{$reset}\n\n";
    } else {
        echo "{$red}✗ Test 1 FAILED: Not all defaults are 5.0{$reset}\n\n";
    }
    
    // Clean up test evaluation
    $repo->delete($result);
} else {
    echo "{$red}✗ Test 1 FAILED: " . $result->get_error_message() . "{$reset}\n\n";
}

// Test 2: Check that evaluations appear in rankings without filter
echo "Test 2: New evaluations should appear in rankings\n";
$repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();

// Create a test evaluation
$test_eval_id = $repo->create([
    'jury_member_id' => 1,
    'candidate_id' => 1,
    'courage_score' => 5.0,
    'innovation_score' => 5.0,
    'implementation_score' => 5.0,
    'relevance_score' => 5.0,
    'visibility_score' => 5.0,
    'total_score' => 5.0,
    'status' => 'draft',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
]);

if ($test_eval_id) {
    // Get rankings for jury member
    $rankings = $repo->get_ranked_candidates_for_jury(1, 10);
    
    $found = false;
    foreach ($rankings as $ranking) {
        if ($ranking->candidate_id == 1) {
            $found = true;
            break;
        }
    }
    
    if ($found) {
        echo "{$green}✓ Test 2 PASSED: New evaluation appears in rankings{$reset}\n\n";
    } else {
        echo "{$red}✗ Test 2 FAILED: New evaluation not in rankings{$reset}\n\n";
    }
    
    // Clean up
    $repo->delete($test_eval_id);
} else {
    echo "{$red}✗ Test 2 FAILED: Could not create test evaluation{$reset}\n\n";
}

// Test 3: Check find_all with proper where syntax
echo "Test 3: find_all should use proper 'where' syntax\n";
$test_eval_id = $repo->create([
    'jury_member_id' => 2,
    'candidate_id' => 2,
    'courage_score' => 7.0,
    'innovation_score' => 7.0,
    'implementation_score' => 7.0,
    'relevance_score' => 7.0,
    'visibility_score' => 7.0,
    'total_score' => 7.0,
    'status' => 'submitted',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
]);

if ($test_eval_id) {
    // Test proper syntax
    $results = $repo->find_all([
        'where' => [
            'jury_member_id' => 2,
            'candidate_id' => 2
        ]
    ]);
    
    if (count($results) == 1 && $results[0]->id == $test_eval_id) {
        echo "{$green}✓ Test 3 PASSED: find_all with proper 'where' syntax works{$reset}\n\n";
    } else {
        echo "{$red}✗ Test 3 FAILED: find_all returned " . count($results) . " results{$reset}\n\n";
    }
    
    // Clean up
    $repo->delete($test_eval_id);
} else {
    echo "{$red}✗ Test 3 FAILED: Could not create test evaluation{$reset}\n\n";
}

// Test 4: Verify total_score calculation includes 0 values
echo "Test 4: Total score should include 0 values in average\n";
$test_eval_id = $repo->create([
    'jury_member_id' => 3,
    'candidate_id' => 3,
    'courage_score' => 0,
    'innovation_score' => 10,
    'implementation_score' => 0,
    'relevance_score' => 10,
    'visibility_score' => 0,
    'status' => 'draft',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
]);

if ($test_eval_id) {
    // Calculate total score
    $repo->calculate_total_score($test_eval_id);
    $eval = $repo->find($test_eval_id);
    
    // Expected: (0 + 10 + 0 + 10 + 0) / 5 = 4.0
    $expected_total = 4.0;
    
    if (abs($eval->total_score - $expected_total) < 0.01) {
        echo "{$green}✓ Test 4 PASSED: Total score correctly includes 0 values (got {$eval->total_score}){$reset}\n\n";
    } else {
        echo "{$red}✗ Test 4 FAILED: Expected $expected_total, got {$eval->total_score}{$reset}\n\n";
    }
    
    // Clean up
    $repo->delete($test_eval_id);
} else {
    echo "{$red}✗ Test 4 FAILED: Could not create test evaluation{$reset}\n\n";
}

echo "{$yellow}Testing Complete!{$reset}\n";