Elegant Solution for Candidate Management Without CPT

    Overview

    Implement a complete candidate management system using ONLY the wp_mt_candidates custom table as the single
    source of truth, following Phase 2 architectural guidelines.

    Implementation Plan

    1. Create Custom Admin Page for Candidates

    - Add new submenu under Mobility Trailblazers for "Candidates"
    - Build custom list table extending WP_List_Table for candidate listing
    - Implement add/edit/delete functionality using repository pattern
    - Include inline editing capabilities

    2. Fix Routing for Individual Candidate Pages

    - Implement custom rewrite rules for /candidate/slug/ URLs
    - Register query var mt_candidate_slug
    - Handle template loading through template_include filter
    - Load candidate data from repository by slug
    - Create virtual post object for template compatibility

    3. Build Edit Interface Without CPT

    - Create custom admin page for editing candidates
    - Form fields for all candidate data (name, organization, position, etc.)
    - Rich text editor for description sections
    - Media uploader for candidate photos
    - Save directly to wp_mt_candidates table via repository

    4. Repository Enhancements

    - Add methods for creating new candidates
    - Enhance update methods for all fields
    - Add slug generation and validation
    - Implement photo attachment handling

    5. Template System Updates

    - Fix template loader to work with repository data
    - Update single candidate templates to use $GLOBALS['mt_current_candidate']
    - Ensure backward compatibility for existing templates

    6. Features to Implement

    - Bulk actions (delete, export)
    - Search and filtering
    - Quick edit functionality
    - Photo management
    - Description section editor
    - German translation support

    Files to Create/Modify

    New Files:
    1. includes/admin/class-mt-candidates-admin.php - Main admin interface
    2. includes/admin/class-mt-candidates-list-table.php - List table for candidates
    3. includes/admin/class-mt-candidate-edit-page.php - Edit page handler
    4. templates/admin/candidates-list.php - List view template
    5. templates/admin/candidate-edit.php - Edit form template

    Modify Files:
    1. includes/core/class-mt-plugin.php - Remove CPT router reference
    2. includes/core/class-mt-template-loader.php - Fix template loading
    3. includes/repositories/class-mt-candidate-repository.php - Add CRUD methods
    4. mobility-trailblazers.php - Add routing hooks
    5. includes/admin/class-mt-admin.php - Add candidates menu

    Benefits

    - Single source of truth (wp_mt_candidates table only)
    - No CPT dependency
    - Clean architecture following repository pattern
    - Full editing capabilities
    - Better performance (direct table queries)
    - Maintains all existing features

    This solution provides a clean, maintainable approach that aligns with the Phase 2 architecture while providing
     all necessary functionality for managing candidates.