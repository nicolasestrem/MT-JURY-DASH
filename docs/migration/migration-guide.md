# Mobility Trailblazers Migration Guide

## From CPT to Repository Pattern - Step-by-Step Guide

This guide provides detailed instructions for migrating the Mobility Trailblazers platform from WordPress Custom Post Types to the new repository-based architecture.

## Table of Contents

1. [Pre-Migration Checklist](#pre-migration-checklist)
2. [Migration Process](#migration-process)
3. [Verification Steps](#verification-steps)
4. [Code Migration Examples](#code-migration-examples)
5. [Rollback Procedures](#rollback-procedures)
6. [FAQ](#faq)

## Pre-Migration Checklist

### System Requirements
- [ ] WordPress 5.8 or higher
- [ ] PHP 7.4 or higher (8.2+ recommended)
- [ ] MySQL 5.7+ or MariaDB 10.3+
- [ ] WP-CLI installed (optional but recommended)
- [ ] Backup system in place

### Pre-Migration Tasks
1. **Create Full Backup**
   ```bash
   # Database backup
   wp db export backup-$(date +%Y%m%d-%H%M%S).sql
   
   # Files backup
   tar -czf mt-plugin-backup-$(date +%Y%m%d).tar.gz Plugin/
   ```

2. **Check Current Data**
   ```sql
   -- Count existing candidates
   SELECT COUNT(*) FROM wp_posts WHERE post_type = 'mt_candidate';
   
   -- Check meta data
   SELECT COUNT(DISTINCT post_id) FROM wp_postmeta 
   WHERE meta_key LIKE '_mt_%';
   ```

3. **Clear Caches**
   ```bash
   wp cache flush
   wp transient delete --all
   ```

## Migration Process

### Option 1: WP-CLI Migration (Recommended)

#### Step 1: Dry Run
```bash
# Preview what will be migrated without making changes
wp mt migrate-candidates --dry-run

# Example output:
# Migration Preview (DRY RUN):
# - Found 50 candidates to migrate
# - Estimated time: 2-3 minutes
# - No changes will be made
```

#### Step 2: Create Backup
```bash
# Create backup with timestamp
wp mt migrate-candidates --backup

# Output:
# Backup created: wp_mt_candidates_backup_20250904_120000.sql
```

#### Step 3: Run Migration
```bash
# Run with default batch size (50)
wp mt migrate-candidates

# Or with custom batch size for large datasets
wp mt migrate-candidates --batch-size=100

# Monitor progress
# Processing batch 1/10 (records 1-50)...
# Processing batch 2/10 (records 51-100)...
# Migration complete: 500 candidates migrated successfully
```

#### Step 4: Verify Migration
```bash
# Run verification
wp mt migrate-candidates --verify

# Output:
# Verification Results:
# ✅ Total candidates migrated: 500/500
# ✅ Post ID mappings: 500/500 valid
# ✅ Data integrity: 100% match
# ✅ No orphaned records found
```

### Option 2: Admin Interface Migration

1. Navigate to **WordPress Admin → MT Award System → Data Migration**

2. **Preview Migration**
   - Click "Preview Migration"
   - Review the migration summary
   - Check estimated time and record count

3. **Configure Settings**
   ```
   Batch Size: [50] records per batch
   ☑️ Create backup before migration
   ☑️ Verify after migration
   ☐ Skip existing records
   ```

4. **Start Migration**
   - Click "Start Migration"
   - Monitor progress bar
   - Do not close browser during migration

5. **Review Results**
   - Check migration log
   - Verify success count matches expected
   - Review any error messages

### Option 3: Programmatic Migration

```php
// Run migration via code
$migration = new MT_CPT_To_Table_Migration();

// Configure options
$options = [
    'batch_size' => 100,
    'dry_run' => false,
    'backup' => true,
    'verify' => true
];

// Run migration
$result = $migration->run($options);

// Check results
if ($result['success']) {
    echo "Migrated: " . $result['migrated'] . " candidates\n";
    echo "Skipped: " . $result['skipped'] . "\n";
    echo "Errors: " . $result['errors'] . "\n";
} else {
    echo "Migration failed: " . $result['message'];
}
```

## Verification Steps

### 1. Database Verification

```sql
-- Check new table exists and has data
SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT post_id) as linked_posts,
    MIN(created_at) as first_migration,
    MAX(created_at) as last_migration
FROM wp_mt_candidates;

-- Verify data integrity
SELECT 
    c.id,
    c.name,
    c.post_id,
    p.post_title,
    CASE 
        WHEN c.name = p.post_title THEN 'MATCH'
        ELSE 'MISMATCH'
    END as title_check
FROM wp_mt_candidates c
LEFT JOIN wp_posts p ON c.post_id = p.ID
WHERE p.post_type = 'mt_candidate'
LIMIT 10;

-- Check for missing data
SELECT post_id, name, organization 
FROM wp_mt_candidates 
WHERE organization IS NULL OR organization = ''
LIMIT 10;
```

### 2. Frontend Verification

1. **Candidate Display**
   - Visit candidate archive page
   - Check individual candidate profiles
   - Verify all fields display correctly

2. **Jury Dashboard**
   - Login as jury member
   - Check assigned candidates appear
   - Verify candidate details load

3. **Evaluation Form**
   - Open evaluation form
   - Confirm candidate data populates
   - Submit test evaluation

### 3. Admin Verification

1. **Admin List**
   ```
   Admin → Candidates
   - All columns display data
   - Sorting works
   - Filtering functional
   ```

2. **Import/Export**
   ```bash
   # Test export
   Admin → Candidates → Export CSV
   
   # Verify CSV contains all fields
   # Test reimport with small batch
   ```

### 4. Performance Verification

```php
// Compare query performance
$start = microtime(true);

// Old method
$candidates_old = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => 100
]);
foreach ($candidates_old as $candidate) {
    $org = get_post_meta($candidate->ID, '_mt_organization', true);
}
$time_old = microtime(true) - $start;

// New method
$start = microtime(true);
$candidates_new = mt_get_all_candidates(['limit' => 100]);
foreach ($candidates_new as $candidate) {
    $org = $candidate->organization;
}
$time_new = microtime(true) - $start;

echo "Old method: {$time_old}s\n";
echo "New method: {$time_new}s\n";
echo "Improvement: " . round((1 - $time_new/$time_old) * 100) . "%\n";
```

## Code Migration Examples

### Example 1: Getting Candidate Data

**Before (CPT):**
```php
// Get candidate
$candidate = get_post($candidate_id);
$title = $candidate->post_title;
$content = $candidate->post_content;

// Get metadata
$organization = get_post_meta($candidate_id, '_mt_organization', true);
$position = get_post_meta($candidate_id, '_mt_position', true);
$linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true);

// Get category
$categories = wp_get_post_terms($candidate_id, 'mt_award_category');
$category = !empty($categories) ? $categories[0]->name : '';
```

**After (Repository):**
```php
// Get candidate - works with both ID types
$candidate = mt_get_candidate($candidate_id);

// Direct property access
$title = $candidate->name;
$organization = $candidate->organization;
$position = $candidate->position;
$linkedin = $candidate->linkedin_url;

// Get from JSON sections
$sections = json_decode($candidate->description_sections, true);
$content = $sections['description'] ?? '';
$category = $sections['category'] ?? '';
```

### Example 2: Querying Multiple Candidates

**Before (CPT):**
```php
$args = [
    'post_type' => 'mt_candidate',
    'posts_per_page' => 20,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
        [
            'key' => '_mt_organization',
            'value' => 'Tech Corp',
            'compare' => 'LIKE'
        ]
    ]
];
$candidates = get_posts($args);
```

**After (Repository):**
```php
// Using repository directly
$repository = mt_get_candidate_repository();
$candidates = $repository->find_all([
    'limit' => 20,
    'orderby' => 'name',
    'order' => 'ASC',
    'where' => [
        'organization' => ['LIKE', '%Tech Corp%']
    ]
]);

// Or using helper function
$candidates = mt_get_all_candidates([
    'limit' => 20,
    'organization' => 'Tech Corp'
]);
```

### Example 3: Template Updates

**Before (in template file):**
```php
<?php while (have_posts()) : the_post(); ?>
    <?php 
    $org = get_post_meta(get_the_ID(), '_mt_organization', true);
    $pos = get_post_meta(get_the_ID(), '_mt_position', true);
    ?>
    <h2><?php the_title(); ?></h2>
    <p><?php echo esc_html($org); ?> - <?php echo esc_html($pos); ?></p>
<?php endwhile; ?>
```

**After (in template file):**
```php
<?php while (have_posts()) : the_post(); ?>
    <?php 
    $candidate = mt_get_candidate_by_post_id(get_the_ID());
    ?>
    <h2><?php echo esc_html($candidate->name); ?></h2>
    <p><?php echo esc_html($candidate->organization); ?> - <?php echo esc_html($candidate->position); ?></p>
<?php endwhile; ?>
```

### Example 4: AJAX Handler Updates

**Before:**
```php
public function get_candidate_details() {
    $candidate_id = intval($_POST['candidate_id']);
    $candidate = get_post($candidate_id);
    
    $response = [
        'name' => $candidate->post_title,
        'organization' => get_post_meta($candidate_id, '_mt_organization', true),
        'position' => get_post_meta($candidate_id, '_mt_position', true)
    ];
    
    wp_send_json_success($response);
}
```

**After:**
```php
public function get_candidate_details() {
    $candidate_id = intval($_POST['candidate_id']);
    $candidate = mt_get_candidate($candidate_id);
    
    $response = [
        'name' => $candidate->name,
        'organization' => $candidate->organization,
        'position' => $candidate->position
    ];
    
    wp_send_json_success($response);
}
```

## Rollback Procedures

### Immediate Rollback (Within 24 Hours)

```bash
# 1. Restore from automatic backup
wp mt migrate-candidates --restore-backup=auto

# 2. Or restore specific backup
wp mt migrate-candidates --restore-backup=backup_20250904_120000.sql

# 3. Clear caches
wp cache flush
```

### Manual Rollback

```php
// Step 1: Truncate new table
global $wpdb;
$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_candidates");

// Step 2: Re-enable CPT UI (if hidden)
add_filter('mt_show_cpt_ui', '__return_true');

// Step 3: Clear all caches
wp_cache_flush();
delete_transient('mt_candidates_cache');

// Step 4: Verify CPT data intact
$count = wp_count_posts('mt_candidate');
echo "CPT candidates: " . $count->publish;
```

### Partial Rollback (Specific Records)

```php
// Remove specific migrated records
$repository = new MT_Candidate_Repository();

// By import batch
$repository->delete_by_import_batch('batch_20250904');

// By date range
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}mt_candidates 
     WHERE created_at BETWEEN %s AND %s",
    '2025-09-04 00:00:00',
    '2025-09-04 23:59:59'
));
```

## FAQ

### Q: Will the migration affect live site performance?
**A:** The migration runs in batches to minimize impact. Each batch processes 50 records by default, with a small delay between batches. For large sites, run migration during off-peak hours.

### Q: Can I run the migration multiple times?
**A:** Yes, the migration script detects existing records and skips them. Use `--force` flag to re-migrate existing records.

### Q: What happens to the old CPT data?
**A:** CPT data remains intact but becomes hidden from the UI. It serves as a backup and can be cleaned up after verification.

### Q: How do I handle custom meta fields?
**A:** Custom meta fields are automatically migrated to the `description_sections` JSON field. Access them using:
```php
$sections = json_decode($candidate->description_sections, true);
$custom_value = $sections['your_custom_field'] ?? '';
```

### Q: Is the migration reversible?
**A:** Yes, full rollback is possible within the first 30 days. Automatic backups are created, and CPT data remains untouched.

### Q: What about SEO and permalinks?
**A:** URLs remain unchanged as the system maintains post_id relationships. No SEO impact or redirects needed.

### Q: How do I verify data integrity?
**A:** Run `wp mt migrate-candidates --verify` or use the verification queries provided in this guide.

### Q: What if migration fails halfway?
**A:** The migration is transaction-safe. Partial batches are rolled back automatically. Simply restart the migration to continue from where it stopped.

## Support

For issues or questions:
1. Check the Debug Center: **Admin → MT Award System → Debug Center**
2. Review logs in `/wp-content/debug.log`
3. Run diagnostics: `wp mt diagnose`
4. Contact support with migration ID and error logs

---

*Last Updated: September 4, 2025*  
*Version: 2.5.42*