# Task: Implement Phase 2 Architectural Unification for Mobility Trailblazers Plugin

  ## Context & Objective
  You are tasked with implementing **Phase 2: Architectural Unification & Data Integrity** from the action plan
  located at `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\gemini_report\action-plan.md`. This phase addresses critical
  architectural issues identified in the audit report at
  `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\gemini_report\audit-report.md`.

  ## Primary Goals
  1. **Establish Single Source of Truth** (Section 2.1): Consolidate all candidate data into the `wp_mt_candidates`
  custom table, deprecating the legacy `mt_candidate` Custom Post Type
  2. **Modernize Internationalization** (Section 2.2): Replace non-standard German translation fallback with proper
  WordPress i18n methods

  ## Execution Requirements

  ### Git Workflow
  - Create a new feature branch from `develop` named `feature/phase-2-architectural-unification`
  - Use semantic commits following the pattern: `type(scope): description`
  - Document all changes before committing

  ### Implementation Steps

  #### Step 1: Analysis & Planning
  1. Read and analyze both documents thoroughly:
     - Action plan: Focus on Phase 2 (lines 28-50)
     - Audit report: Review findings MT-003 and MT-004 for context
  2. Use `mcp__sequential-thinking__sequentialthinking` to create a detailed implementation strategy
  3. Document your understanding and approach

  #### Step 2: Data Migration (Section 2.1)
  1. Audit the codebase for all CPT-based data access patterns
  2. Create a migration script (WP-CLI command or admin tool)
  3. Refactor all data access to use `MT_Candidate_Repository`
  4. Deprecate the CPT without breaking existing functionality
  5. Key files to refactor:
     - `class-mt-import-export.php`
     - `class-mt-post-types.php`
     - Admin page handlers

  #### Step 3: i18n Modernization (Section 2.2)
  1. Audit `german-translation-compatibility.php` for all strings
  2. Ensure all strings exist in `.pot` and `de_DE.po` files
  3. Remove the fallback file and its `require_once` statement
  4. Fix non-translatable strings (e.g., in `uninstall.php`)

  ### Quality Control & Verification

  #### Required Testing Tools
  - **Kapture MCP** (`mcp__kapture`): Capture visual evidence of functionality before/after changes
  - **Playwright Tests**: Run E2E tests after each major change using `npx playwright test`
  - **Database Verification**: Use `mcp__mysql` or `wp db query` to verify data integrity

  #### Verification Checklist
  - [ ] All candidate data successfully migrated to custom table
  - [ ] No data loss during migration
  - [ ] All features continue working with new data source
  - [ ] German translations load correctly without fallback file
  - [ ] No PHP errors or warnings in debug log
  - [ ] All Playwright tests pass

  ### Documentation Requirements
  1. Update relevant documentation files in `/docs/` directory
  2. Add migration instructions for production deployment
  3. Document any breaking changes or deprecations
  4. Include rollback procedures

  ## Expected Deliverables
  1. Working branch with Phase 2 fully implemented
  2. Migration script/tool for production use
  3. Updated documentation reflecting architectural changes
  4. Test results and verification screenshots
  5. Pull request ready for review with comprehensive description

  ## Important Constraints
  - Maintain backward compatibility during transition
  - Do not remove CPT entirely - only deprecate it
  - Ensure zero data loss during migration
  - All changes must be reversible if issues arise
  - Follow existing code patterns and repository-service architecture

  ## Success Criteria
  - Single source of truth established for candidate data
  - All legacy code refactored to use new data source
  - German translations working via standard WordPress i18n
  - No regression in existing functionality
  - Clean audit results for Phase 2 items
  
  
  
  
  
  
  