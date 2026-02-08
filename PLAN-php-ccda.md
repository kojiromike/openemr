# PHP CCDA Generator - Completion Plan

**Goal:** Replace Node.js ccdaservice with native PHP implementation.

**Branch:** `php-ccda` (worktree at `/Users/michael/dev/openemr/php-ccda/`)

**Baseline:** Jerry Padgett's work from PR #10141 (~70-80% complete)

---

## Phase 1: Cleanup & Setup (Day 1) - COMPLETED

### 1.1 Remove duplicate/unused files
- [x] Replaced CcdaDataTransformer with CORRECTED version (cleaner, 327 lines shorter)
- [x] Removed CcdaXmlBuilder.php (instantiated but never used)
- [x] Fixed unqualified Exception, $GLOBALS access, call_user_func issues

### 1.2 Development environment
- [x] Run `composer install` in worktree
- [x] PHPStan passes on committed changes
- [x] PHPCS passes on all new code

### 1.3 Create test infrastructure
- [x] Created `tests/Tests/Isolated/Carecoordination/PhpCcdaBuilder/` directory
- [x] 59 unit tests passing with 96 assertions
- [ ] Set up comparison test framework (Node.js output vs PHP output)
- [ ] Create sample input XML fixtures from real CCDA generation

---

## Phase 2: Data Transformer Completion (Days 2-5)

### 2.1 Audit Results (COMPLETED)

**Overall Assessment: Code is well-structured and ~90% complete.**

23 populate methods reviewed. Key findings:
- [x] Well-structured code with clear separation of concerns
- [x] Consistent null handling via null coalescing operators
- [x] Proper use of DateFormatter and CodeCleaner utilities
- [x] processSection() handles various input structures robustly
- [x] All major CCDA sections covered

**Only 1 TODO found:**
- Line 652: `@todo handle prescribed status` (minor - defaults to 'Completed')

**No results section** (commented out on line 85-86) - this matches Node.js behavior

### 2.2 Remaining Work
- [ ] Fix 1066 PHPStan level 10 errors (type annotations, mixed types)
- [ ] Comparison testing against Node.js output
- [ ] Address the one TODO (medication prescribed status)

---

## Phase 3: Unit Tests (Days 4-7)

Write comprehensive unit tests in `tests/Tests/Unit/Carecoordination/PhpCcdaBuilder/`.

### 3.1 CcdaBuilderTest.php
- [ ] Test XML parsing (valid input)
- [ ] Test XML parsing (invalid/malformed input)
- [ ] Test generate() orchestration flow
- [ ] Test error handling and exceptions
- [ ] Test unstructured document generation

### 3.2 CcdaTemplateEngineTest.php
- [ ] Test processTemplate() with nested structures
- [ ] Test each runKey type: key, content, dataKey, dataTransform, etc.
- [ ] Test fillAttributes() with various attribute types
- [ ] Test edge cases: empty data, null values, missing keys
- [ ] Test template composition/inheritance

### 3.3 CcdaDataTransformerTest.php
- [ ] Test each populate*() method with sample patient data
- [ ] Test null/missing field handling
- [ ] Test code system mappings (ICD-10, SNOMED, RxNorm, LOINC, CVX)
- [ ] Test date/time formatting
- [ ] Test address/telecom normalization

### 3.4 Section Template Tests
Create one test per section template:
- [ ] ProblemsSection - verify structure with 0, 1, N problems
- [ ] MedicationsSection - verify structure with 0, 1, N medications
- [ ] AllergiesSection - verify structure including "no known allergies"
- [ ] VitalsSection - verify LOINC codes and units
- [ ] (etc. for each section)

### 3.5 Utility Tests
- [ ] Test helper functions in Utils/
- [ ] Test code system lookups in CodeSystems/

---

## Phase 4: Template Engine Validation (Days 8-9)

### 3.1 Template coverage
- [ ] Verify all section templates produce valid C-CDA XML
- [ ] Test template inheritance/composition
- [ ] Validate against CDA schema

### 3.2 Edge cases
- [ ] Empty sections (no data)
- [ ] Single item vs multiple items
- [ ] Special characters / encoding
- [ ] Long text fields (narrative blocks)

---

## Phase 5: Integration Testing (Days 10-12)

### 4.1 Comparison testing
- [ ] Generate CCDAs with Node.js engine
- [ ] Generate same CCDAs with PHP engine
- [ ] Diff outputs and resolve differences
- [ ] Test with multiple patient records

### 4.2 Functional testing
- [ ] Test Care Coordination module integration
- [ ] Test Portal CCDA download
- [ ] Test CCDA import (round-trip)
- [ ] Test unstructured document generation

### 4.3 Validation
- [ ] Run through NIST CCDA validator
- [ ] Run through HL7 validator
- [ ] Test with known-good reference CCDAs

---

## Phase 6: Documentation & PR (Days 13-14)

### 5.1 Documentation
- [ ] Update README with new architecture
- [ ] Document global setting options (4, 5)
- [ ] Add inline code comments where needed

### 5.2 PR preparation
- [ ] Squash/organize commits
- [ ] Create comprehensive PR description
- [ ] Reference issue #10024 and PR #10141
- [ ] Credit Jerry Padgett appropriately

---

## Architecture Notes (Future Improvements)

Once Node.js is removed, consider these refactorings:

1. **Eliminate intermediate XML format**
   - Current: PHP → XML blob → parse → transform → CCDA
   - Future: PHP data structures → CCDA directly

2. **Simplify data transformer**
   - Current 2400 lines exists because of format mapping
   - Could shrink significantly with direct data access

3. **Consider service class approach**
   - `CcdaService::generate(Patient $patient, Encounter $encounter)`
   - Direct Doctrine/QueryUtils calls instead of XML parsing

These are post-migration improvements, not blockers for the initial release.

---

## Files to Track

| File | Status | Notes |
|------|--------|-------|
| `CcdaBuilder.php` | Complete | Entry point |
| `CcdaTemplateEngine.php` | Complete | Template processor |
| `CcdaDataTransformer.php` | ~70% | Needs populate method review |
| `CcdaDataTransformer_CORRECTED.php` | Delete | Duplicate |
| `CcdaXmlBuilder.php` | Delete | Unused |
| `Core/LeafLevel.php` | Review | Leaf-level template functions |
| `Core/Fieldlevel.php` | Review | Field-level template functions |
| `Templates/*.php` | Review | Section templates |
| `Utils/*.php` | Review | Utility functions |
| `CodeSystems/*.php` | Review | Code system constants |

---

## Success Criteria

1. PHP engine produces identical output to Node.js for all test cases
2. PHPStan level 10 passes on all new code
3. CCDA validates against NIST validator
4. No Node.js runtime required when using PHP engine
5. Global setting toggle works correctly (modes 4 and 5)
6. Unit tests cover all public methods in PhpCcdaBuilder classes
7. All unit tests pass in CI (composer phpunit-isolated)
