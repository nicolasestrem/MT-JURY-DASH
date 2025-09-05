# Mobility Trailblazers API Reference

## Repository Pattern API Documentation

### Version: 2.5.42
### Last Updated: September 4, 2025

## Table of Contents

1. [Repository Classes](#repository-classes)
2. [Helper Functions](#helper-functions)
3. [Data Models](#data-models)
4. [Query Methods](#query-methods)
5. [Hooks and Filters](#hooks-and-filters)
6. [REST API Endpoints](#rest-api-endpoints)
7. [Error Handling](#error-handling)

## Repository Classes

### MT_Candidate_Repository

Primary repository for candidate data operations.

#### Class Location
```php
namespace MobilityTrailblazers\Repositories;
use MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface;

class MT_Candidate_Repository implements MT_Candidate_Repository_Interface
```

#### Methods

##### find($id)
Finds a candidate by primary ID.

```php
/**
 * Find candidate by ID
 *
 * @param int $id Candidate ID
 * @return object|null Candidate object or null if not found
 */
public function find($id)

// Example
$repository = new MT_Candidate_Repository();
$candidate = $repository->find(123);
if ($candidate) {
    echo $candidate->name; // "John Doe"
    echo $candidate->organization; // "Tech Corp"
}
```

##### find_by_post_id($post_id)
Finds a candidate by WordPress post ID (backward compatibility).

```php
/**
 * Find candidate by WordPress post ID
 *
 * @param int $post_id WordPress post ID
 * @return object|null Candidate object or null
 */
public function find_by_post_id($post_id)

// Example
$candidate = $repository->find_by_post_id(456);
```

##### find_by_import_id($import_id)
Finds a candidate by import ID.

```php
/**
 * Find candidate by import ID
 *
 * @param string $import_id Import identifier
 * @return object|null Candidate object or null
 */
public function find_by_import_id($import_id)

// Example
$candidate = $repository->find_by_import_id('IMP-2025-001');
```

##### find_all($args = [])
Retrieves all candidates with optional filtering.

```php
/**
 * Find all candidates
 *
 * @param array $args Query arguments
 * @return array Array of candidate objects
 */
public function find_all($args = [])

// Example with all parameters
$candidates = $repository->find_all([
    'orderby' => 'name',        // name|organization|created_at|updated_at
    'order' => 'ASC',           // ASC|DESC
    'limit' => 20,              // Number of records
    'offset' => 0,              // Starting position
    'where' => [                // WHERE conditions
        'organization' => 'Tech Corp',
        'country' => ['IN', ['USA', 'Germany', 'France']]
    ],
    'search' => 'innovation',   // Search in name and organization
    'fields' => ['id', 'name', 'organization'] // Specific fields only
]);
```

##### create($data)
Creates a new candidate record.

```php
/**
 * Create new candidate
 *
 * @param array $data Candidate data
 * @return int|false Candidate ID or false on failure
 */
public function create($data)

// Example
$candidate_id = $repository->create([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'organization' => 'Startup Inc',
    'position' => 'CEO',
    'country' => 'Germany',
    'linkedin_url' => 'https://linkedin.com/in/janesmith',
    'website_url' => 'https://startup-inc.com',
    'description_sections' => json_encode([
        'description' => 'Innovative leader in mobility tech...',
        'category' => 'startup',
        'evaluation_courage' => 'Pioneering new approaches...',
        'evaluation_innovation' => 'Breakthrough technology...'
    ])
]);

if ($candidate_id) {
    echo "Created candidate with ID: " . $candidate_id;
}
```

##### update($id, $data)
Updates an existing candidate.

```php
/**
 * Update candidate
 *
 * @param int $id Candidate ID
 * @param array $data Data to update
 * @return bool Success status
 */
public function update($id, $data)

// Example
$success = $repository->update(123, [
    'position' => 'CTO',
    'organization' => 'New Company Ltd'
]);

// Update JSON sections
$sections = json_decode($candidate->description_sections, true);
$sections['category'] = 'tech';
$repository->update(123, [
    'description_sections' => json_encode($sections)
]);
```

##### delete($id)
Deletes a candidate record.

```php
/**
 * Delete candidate
 *
 * @param int $id Candidate ID
 * @return bool Success status
 */
public function delete($id)

// Example
if ($repository->delete(123)) {
    echo "Candidate deleted successfully";
}
```

##### search($keyword, $fields = [])
Searches candidates across specified fields.

```php
/**
 * Search candidates
 *
 * @param string $keyword Search term
 * @param array $fields Fields to search in
 * @return array Array of matching candidates
 */
public function search($keyword, $fields = ['name', 'organization'])

// Example
$results = $repository->search('mobility', [
    'name',
    'organization', 
    'position',
    'description_sections'
]);

foreach ($results as $candidate) {
    echo "{$candidate->name} - {$candidate->organization}\n";
}
```

##### count($where = [])
Counts candidates matching conditions.

```php
/**
 * Count candidates
 *
 * @param array $where WHERE conditions
 * @return int Count
 */
public function count($where = [])

// Example
$total = $repository->count();
$german_count = $repository->count(['country' => 'Germany']);
$startup_count = $repository->count(['description_sections' => ['LIKE', '%"category":"startup"%']]);
```

## Helper Functions

Global helper functions for backward compatibility and ease of use.

### mt_get_candidate($id_or_post_id)

Gets a candidate by ID or post ID, trying both methods.

```php
/**
 * Get candidate by ID or post ID
 * 
 * @param int $id_or_post_id Either table ID or WordPress post ID
 * @return object|null Candidate object or null
 */
function mt_get_candidate($id_or_post_id)

// Example
$candidate = mt_get_candidate(123); // Works with both ID types
if ($candidate) {
    echo $candidate->name;
    echo $candidate->organization;
}
```

### mt_get_candidate_by_post_id($post_id)

Gets a candidate specifically by WordPress post ID.

```php
/**
 * Get candidate by WordPress post ID
 * 
 * @param int $post_id WordPress post ID
 * @return object|null Candidate object or null
 */
function mt_get_candidate_by_post_id($post_id)

// Example - in WordPress loop
while (have_posts()) : the_post();
    $candidate = mt_get_candidate_by_post_id(get_the_ID());
    // Use candidate data
endwhile;
```

### mt_get_all_candidates($args = [])

Gets all candidates with optional filtering.

```php
/**
 * Get all candidates
 * 
 * @param array $args Query arguments
 * @return array Array of candidate objects
 */
function mt_get_all_candidates($args = [])

// Example
$candidates = mt_get_all_candidates([
    'limit' => 10,
    'orderby' => 'name',
    'order' => 'ASC'
]);

foreach ($candidates as $candidate) {
    echo "{$candidate->name} ({$candidate->organization})\n";
}
```

### mt_get_candidate_meta($candidate_id, $meta_key, $single = true)

Backward compatibility function for getting meta values.

```php
/**
 * Get candidate meta value (backward compatibility)
 * 
 * @param int $candidate_id Candidate or post ID
 * @param string $meta_key Meta key
 * @param bool $single Return single value
 * @return mixed Meta value or empty string
 */
function mt_get_candidate_meta($candidate_id, $meta_key, $single = true)

// Example - works with old meta keys
$organization = mt_get_candidate_meta(123, '_mt_organization');
$position = mt_get_candidate_meta(123, '_mt_position');

// Supported meta key mappings
$meta_map = [
    '_mt_organization' => 'organization',
    '_mt_company' => 'organization',      // Alias
    '_mt_position' => 'position',
    '_mt_country' => 'country',
    '_mt_linkedin_url' => 'linkedin_url',
    '_mt_linkedin' => 'linkedin_url',     // Alias
    '_mt_website_url' => 'website_url',
    '_mt_website' => 'website_url',       // Alias
    '_mt_article_url' => 'article_url',
    '_mt_email' => 'email',
    '_mt_import_id' => 'import_id'
];
```

### mt_candidate_to_post($candidate)

Converts a candidate object to WP_Post-like structure.

```php
/**
 * Convert candidate to WP_Post format
 * 
 * @param object $candidate Candidate object
 * @return object WP_Post-like object
 */
function mt_candidate_to_post($candidate)

// Example
$candidate = mt_get_candidate(123);
$post_like = mt_candidate_to_post($candidate);

// Now works with functions expecting WP_Post
echo $post_like->ID;           // Post ID
echo $post_like->post_title;   // Candidate name
echo $post_like->post_content; // Description
echo $post_like->post_type;    // 'mt_candidate'
```

### mt_get_candidate_repository()

Gets the singleton repository instance.

```php
/**
 * Get repository instance
 * 
 * @return MT_Candidate_Repository
 */
function mt_get_candidate_repository()

// Example
$repository = mt_get_candidate_repository();
$candidate = $repository->find(123);
```

## Data Models

### Candidate Object Structure

```php
stdClass Object (
    [id] => 123                          // Primary key
    [post_id] => 456                     // WordPress post ID (if linked)
    [import_id] => "IMP-2025-001"        // Import identifier
    [name] => "John Doe"                 // Candidate name
    [slug] => "john-doe"                 // URL slug
    [email] => "john@example.com"        // Email address
    [organization] => "Tech Corp"        // Organization name
    [position] => "CEO"                  // Position/title
    [country] => "Germany"               // Country
    [linkedin_url] => "https://..."      // LinkedIn profile
    [website_url] => "https://..."       // Website
    [article_url] => "https://..."       // Article/press
    [photo_url] => "https://..."         // Profile photo
    [description_sections] => "{...}"    // JSON data
    [created_at] => "2025-09-04 12:00:00"
    [updated_at] => "2025-09-04 14:30:00"
)
```

### Description Sections JSON Structure

```json
{
    "description": "Full candidate description text",
    "overview": "Brief overview or summary",
    "category": "startup|tech|gov",
    "evaluation_courage": "Courage criteria description",
    "evaluation_innovation": "Innovation criteria description",
    "evaluation_implementation": "Implementation criteria description",
    "evaluation_relevance": "Relevance criteria description",
    "evaluation_visibility": "Visibility criteria description",
    "top_50_status": "yes|no",
    "custom_field_1": "Custom value",
    "custom_field_2": "Custom value"
}
```

## Query Methods

### Advanced Querying

```php
// Complex WHERE conditions
$candidates = $repository->find_all([
    'where' => [
        'organization' => ['LIKE', '%Tech%'],
        'country' => ['IN', ['USA', 'Germany', 'UK']],
        'created_at' => ['>', '2025-01-01'],
        'description_sections' => ['LIKE', '%"category":"startup"%']
    ],
    'orderby' => 'created_at',
    'order' => 'DESC',
    'limit' => 50
]);

// Combining search with filters
$candidates = $repository->find_all([
    'search' => 'innovation',
    'where' => [
        'country' => 'Germany'
    ]
]);

// Pagination
$page = 2;
$per_page = 20;
$candidates = $repository->find_all([
    'limit' => $per_page,
    'offset' => ($page - 1) * $per_page
]);

// Get specific fields only
$candidates = $repository->find_all([
    'fields' => ['id', 'name', 'organization', 'email'],
    'limit' => 100
]);
```

### Aggregation Queries

```php
// Count by criteria
$stats = [
    'total' => $repository->count(),
    'germany' => $repository->count(['country' => 'Germany']),
    'startups' => $repository->count([
        'description_sections' => ['LIKE', '%"category":"startup"%']
    ])
];

// Group by organization (custom query)
global $wpdb;
$orgs = $wpdb->get_results("
    SELECT organization, COUNT(*) as count
    FROM {$wpdb->prefix}mt_candidates
    GROUP BY organization
    ORDER BY count DESC
    LIMIT 10
");
```

## Hooks and Filters

### Available Filters

```php
// Modify repository query arguments
add_filter('mt_candidate_repository_query_args', function($args) {
    // Force specific ordering
    $args['orderby'] = 'name';
    $args['order'] = 'ASC';
    return $args;
});

// Modify candidate data before save
add_filter('mt_candidate_before_save', function($data) {
    // Ensure organization is uppercase
    if (isset($data['organization'])) {
        $data['organization'] = strtoupper($data['organization']);
    }
    return $data;
});

// Modify candidate object after retrieval
add_filter('mt_candidate_after_find', function($candidate) {
    // Add computed property
    $candidate->display_name = $candidate->name . ' - ' . $candidate->organization;
    return $candidate;
});

// Filter search fields
add_filter('mt_candidate_search_fields', function($fields) {
    // Add position to search fields
    $fields[] = 'position';
    return $fields;
});
```

### Available Actions

```php
// After candidate created
add_action('mt_candidate_created', function($candidate_id, $data) {
    // Send notification
    wp_mail('admin@example.com', 'New Candidate', 'ID: ' . $candidate_id);
}, 10, 2);

// After candidate updated
add_action('mt_candidate_updated', function($candidate_id, $data, $old_data) {
    // Log changes
    error_log("Candidate {$candidate_id} updated");
}, 10, 3);

// Before candidate deleted
add_action('mt_candidate_before_delete', function($candidate_id) {
    // Backup data
    $candidate = mt_get_candidate($candidate_id);
    // Save backup...
});

// After migration completed
add_action('mt_migration_completed', function($stats) {
    // Clear all caches
    wp_cache_flush();
});
```

## REST API Endpoints

### Get All Candidates
```
GET /wp-json/mt/v1/candidates

Query Parameters:
- page (int): Page number
- per_page (int): Items per page
- orderby (string): Sort field
- order (string): ASC or DESC
- search (string): Search term
- organization (string): Filter by organization
- country (string): Filter by country
```

### Get Single Candidate
```
GET /wp-json/mt/v1/candidates/{id}

Returns full candidate object with all fields
```

### Create Candidate
```
POST /wp-json/mt/v1/candidates

Body (JSON):
{
    "name": "John Doe",
    "email": "john@example.com",
    "organization": "Tech Corp",
    "position": "CEO",
    "description_sections": {}
}
```

### Update Candidate
```
PUT /wp-json/mt/v1/candidates/{id}

Body (JSON):
{
    "organization": "New Company",
    "position": "CTO"
}
```

### Delete Candidate
```
DELETE /wp-json/mt/v1/candidates/{id}
```

### Search Candidates
```
GET /wp-json/mt/v1/candidates/search

Query Parameters:
- q (string): Search query
- fields (array): Fields to search in
```

## Error Handling

### Repository Exceptions

```php
try {
    $candidate = $repository->find(999999);
    if (!$candidate) {
        throw new Exception('Candidate not found');
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    // Handle error
}
```

### Validation Errors

```php
// Create with validation
$data = [
    'name' => '', // Invalid - empty
    'email' => 'invalid-email' // Invalid format
];

$candidate_id = $repository->create($data);
if (!$candidate_id) {
    $errors = $repository->get_errors();
    foreach ($errors as $field => $message) {
        echo "Error in {$field}: {$message}\n";
    }
}
```

### Database Errors

```php
// Using repository with error handling
$repository = new MT_Candidate_Repository();
$repository->on_error(function($error) {
    // Log to custom error handler
    MT_Logger::error('Repository error', [
        'message' => $error->getMessage(),
        'query' => $error->getQuery()
    ]);
});
```

### Migration Errors

```php
$migration = new MT_CPT_To_Table_Migration();
$result = $migration->run();

if (!$result['success']) {
    // Check specific error types
    if ($result['error_code'] === 'TABLE_NOT_FOUND') {
        // Create table
        $migration->create_table();
    } elseif ($result['error_code'] === 'DUPLICATE_ENTRY') {
        // Handle duplicates
        $migration->skip_duplicates = true;
        $migration->run();
    }
}
```

## Best Practices

### Performance Optimization

```php
// Use field limiting for large datasets
$candidates = $repository->find_all([
    'fields' => ['id', 'name', 'organization'],
    'limit' => 1000
]);

// Batch operations
$batch_size = 100;
for ($offset = 0; ; $offset += $batch_size) {
    $batch = $repository->find_all([
        'limit' => $batch_size,
        'offset' => $offset
    ]);
    
    if (empty($batch)) break;
    
    // Process batch
    foreach ($batch as $candidate) {
        // Process...
    }
}

// Cache results
$cache_key = 'mt_candidates_' . md5(serialize($args));
$candidates = wp_cache_get($cache_key);
if (!$candidates) {
    $candidates = $repository->find_all($args);
    wp_cache_set($cache_key, $candidates, '', 3600); // 1 hour
}
```

### Security

```php
// Always sanitize input
$candidate_id = intval($_POST['candidate_id']);
$organization = sanitize_text_field($_POST['organization']);

// Use prepared statements (handled by repository)
$candidates = $repository->find_all([
    'where' => [
        'organization' => $organization // Automatically escaped
    ]
]);

// Validate permissions
if (!current_user_can('edit_candidates')) {
    wp_die('Unauthorized');
}

// Nonce verification for forms
if (!wp_verify_nonce($_POST['_wpnonce'], 'candidate_action')) {
    wp_die('Security check failed');
}
```

---

*API Version: 1.0*  
*Last Updated: September 4, 2025*