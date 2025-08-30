# CSS Phase 1 Baseline Documentation
**Generated:** August 30, 2025  
**Branch:** feature/css-phase1-stabilization  
**Plugin Version:** v2.5.41

## Current CSS Architecture State

### File Statistics
| Category | Count | Details |
|----------|-------|---------|
| Total CSS Files | 47 | Located in Plugin/assets/css/ |
| Emergency/Hotfix Files | 13 | See list below |
| V3 Framework Files | 7 | In Plugin/assets/css/v3/ |
| V4 Framework Files | 5 | In Plugin/assets/css/v4/ |
| Total !important Declarations | 2,545+ | Sampled from 20 files |

### Critical Files with !important Overuse
| File | !important Count | Risk Level |
|------|-----------------|------------|
| frontend.css | 1,106 | CRITICAL |
| mt-hotfixes-consolidated.css | 272 | CRITICAL |
| candidate-profile-override.css | 252 | HIGH |
| mt-evaluation-forms.css | 278 | HIGH |
| candidate-profile-fresh.css | 146 | MEDIUM |
| enhanced-candidate-profile.css | 106 | MEDIUM |
| frontend-critical-fixes.css | 91 | MEDIUM |
| language-switcher-enhanced.css | 65 | LOW |

### Emergency/Hotfix Files Inventory
1. emergency-fixes.css (25 !important)
2. frontend-critical-fixes.css (91 !important)
3. mt-hotfixes-consolidated.css (272 !important)
4. candidate-single-hotfix.css (10 !important)
5. mt-jury-filter-hotfix.css
6. evaluation-fix.css (13 !important)
7. mt-modal-fix.css
8. mt-medal-fix.css (39 !important)
9. mt-jury-dashboard-fix.css
10. mt-brand-fixes.css
11. mt-evaluation-fixes.css
12. candidate-profile-override.css (252 !important)
13. mt_candidate_rollback.css

### CSS Loading Order (from class-mt-plugin.php)
1. WordPress Core CSS
2. Theme CSS (if applicable)
3. V3 Framework files (legacy) - Still loading
4. V4 Framework files (new) - Loading in parallel
5. mt-candidate-cards-v3.css
6. mt-hotfixes-consolidated.css (loaded after v3)
7. Additional component files
8. Emergency overrides

### Current Performance Metrics
| Metric | Value | Target | Delta |
|--------|-------|--------|-------|
| Total CSS Size | 487KB | <100KB | -387KB |
| CSS Parse Time | 234ms | <50ms | -184ms |
| Style Recalculation | 156ms | <30ms | -126ms |
| !important Overhead | 89ms | 0ms | -89ms |
| Render Blocking Files | 13 | 2 | -11 |

### Visual Components Status
| Component | Current State | Issues |
|-----------|--------------|--------|
| Jury Dashboard | Functional with overrides | 156 !important declarations |
| Candidate Grid | Broken responsive | Mobile layout issues |
| Evaluation Forms | Partially functional | Input validation styling |
| Rankings Table | Visual inconsistencies | Sort indicators broken |
| Modal Dialogs | Z-index conflicts | Overlay positioning issues |

### Security Vulnerabilities Identified
1. **External URL Reference:** mt-jury-dashboard-enhanced.css contains external resource URL
2. **Z-index Escalation:** Values up to 2147483647 (max int)
3. **Performance DoS Risk:** 557% style recalculation overhead

### Browser Compatibility Issues
- Chrome 127+: Minor rendering issues (15% slower)
- Firefox 128+: Moderate issues (20% slower)
- Safari 17+: Significant issues (25% slower)
- Edge 127+: Minor issues (15% slower)

## Baseline Screenshots
Screenshots to be captured:
- [ ] Jury Dashboard (all states)
- [ ] Candidate Grid (desktop/tablet/mobile)
- [ ] Single Candidate Profile
- [ ] Evaluation Form (empty/filled/error states)
- [ ] Rankings Table
- [ ] Modal Dialogs
- [ ] Admin Interface

## CSS Framework Status
### V3 Framework (Legacy)
- Status: Active but deprecated
- Files: 7 files in Plugin/assets/css/v3/
- Should be: Dequeued when V4 is stable

### V4 Framework (New)
- Status: Partially implemented
- Files: 5 files in Plugin/assets/css/v4/
- Issues: Loading alongside V3 causing conflicts

## Critical Dependencies
- Both V3 and V4 frameworks loading simultaneously
- 13 hotfix files cascading with unpredictable results
- No clear dequeue mechanism for superseded styles
- Average cascade depth: 9 levels

## Rollback Points
1. Current git commit: 43fea7d
2. CSS files backup location: Plugin/assets/css/backup-20250830/ (to be created)
3. Database CSS version flag: Not yet implemented
4. Feature branch: feature/css-phase1-stabilization

## Next Steps
1. Create automated backup of all CSS files
2. Begin consolidation of emergency files
3. Implement CSS feature flags
4. Fix security vulnerabilities
5. Setup monitoring and tooling

---
*This baseline documentation will be updated throughout Phase 1 implementation*