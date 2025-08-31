<?php
/**
 * Test auto-assignment functionality
 */

// Load WordPress
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Get services
$assignment_repo = mt_assignment_repository();

echo "Testing Auto-Assignment Functionality\n";
echo "=====================================\n\n";

// Get counts
$jury_count = wp_count_posts('mt_jury_member')->publish;
$candidate_count = wp_count_posts('mt_candidate')->publish;

echo "Available resources:\n";
echo "  Jury members: $jury_count\n";
echo "  Candidates: $candidate_count\n\n";

// Test auto-distribution
echo "Running auto-distribution...\n";
$options = [
    'candidates_per_jury' => 3,
    'max_jury_per_candidate' => 2,
    'clear_existing' => false
];

$results = $assignment_repo->auto_distribute($options);

echo "\nResults:\n";
echo "  Created: " . ($results['created'] ?? 0) . " new assignments\n";
echo "  Skipped: " . ($results['skipped'] ?? 0) . " existing assignments\n";

if (!empty($results['errors'])) {
    echo "  Errors:\n";
    foreach ($results['errors'] as $error) {
        echo "    - $error\n";
    }
}

// Show final statistics
$stats = $assignment_repo->get_statistics();
echo "\nFinal Statistics:\n";
echo "  Total assignments: " . ($stats['total_assignments'] ?? 0) . "\n";
echo "  Unique jury members: " . ($stats['unique_jury_members'] ?? 0) . "\n";  
echo "  Unique candidates: " . ($stats['unique_candidates'] ?? 0) . "\n";

echo "\n✓ Test completed!\n";