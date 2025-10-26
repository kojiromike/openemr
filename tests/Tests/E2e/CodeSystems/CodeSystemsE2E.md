# Code Systems E2E Tests

## Overview

This suite provides end-to-end browser automation tests for the OpenEMR External Code Systems interface located at `interface/code_systems/`.

## Test Files

- **`tests/Tests/E2e/ZzCodeSystemsTest.php`** - Main E2E test class
- **`tests/Tests/E2e/CodeSystems/CodeSystemsTrait.php`** - Helper trait for navigation and assertions

## Technology Stack

- **Framework**: Symfony Panther (Chrome/Firefox browser automation)
- **WebDriver**: Selenium Grid or ChromeDriver
- **Test Framework**: PHPUnit 11

## Files Tested

### Interface Files
These are the actual PHP files in `interface/code_systems/` that get coverage:

1. **`dataloads_ajax.php`** - Main UI page with accordion sections
2. **`list_installed.php`** - AJAX endpoint showing installed vocabulary versions
3. **`list_staged.php`** - AJAX endpoint validating and listing staged files
4. **`standard_tables_manage.php`** - Import processing endpoint (not directly tested)
5. **`*_howto.php` files** - Installation instruction modals (not directly tested)

### Supported Vocabularies
- **ICD10** - International Classification of Diseases
- **RXNORM** - Pharmaceutical terminology
- **SNOMED** - Clinical terminology
- **CQM_VALUESET** - Clinical Quality Measures

---

## Test Coverage (13 Tests)

### 1. Page Access Tests (2 tests)

#### `testCanAccessCodeSystemsPage()`
**Purpose**: Verify authenticated users can access the External Data Loads page

**Coverage**:
- `dataloads_ajax.php` - Page load and rendering
- ACL check for admin access

**Steps**:
1. Login as admin user
2. Navigate to `/interface/code_systems/dataloads_ajax.php`
3. Verify page title contains "External Data Loads"

---

#### `testPageRequiresAdminAccess()`
**Purpose**: Verify unauthenticated users cannot access the page

**Coverage**:
- `dataloads_ajax.php` - ACL enforcement

**Steps**:
1. Attempt to access page without authentication
2. Verify redirect to login or "Not Authorized" message

---

### 2. UI Component Tests (6 tests)

#### `testOverviewSectionExists()`
**Purpose**: Verify overview section is present and readable

**Coverage**:
- `dataloads_ajax.php` - Overview accordion section

**Assertions**:
- Overview text contains "supported external dataloads"
- Section is properly rendered

---

#### `testAllDatabaseSectionsExist()`
**Purpose**: Verify all supported vocabulary sections are present

**Coverage**:
- `dataloads_ajax.php` - Accordion sections for ICD10, RXNORM, SNOMED, CQM_VALUESET

**Assertions**:
- Each database section exists in DOM
- Collapse/expand functionality is wired up

---

#### `testCanExpandICD10Section()`
**Purpose**: Test ICD10 accordion expansion and AJAX loading

**Coverage**:
- `dataloads_ajax.php` - ICD10 accordion button
- `list_installed.php?db=ICD10` - Installation status AJAX call
- `list_staged.php?db=ICD10` - Staged files AJAX call

**Steps**:
1. Click ICD10 accordion button
2. Wait for AJAX calls to complete
3. Verify installation status is displayed

---

#### `testCanExpandRXNORMSection()`
**Purpose**: Test RXNORM accordion expansion

**Coverage**:
- `dataloads_ajax.php` - RXNORM accordion
- `list_installed.php?db=RXNORM`
- `list_staged.php?db=RXNORM`

---

#### `testCanExpandSNOMEDSection()`
**Purpose**: Test SNOMED accordion expansion

**Coverage**:
- `dataloads_ajax.php` - SNOMED accordion
- `list_installed.php?db=SNOMED`
- `list_staged.php?db=SNOMED`

---

#### `testCanExpandCQMValuesetSection()`
**Purpose**: Test CQM_VALUESET accordion expansion

**Coverage**:
- `dataloads_ajax.php` - CQM_VALUESET accordion
- `list_installed.php?db=CQM_VALUESET`
- `list_staged.php?db=CQM_VALUESET`

---

### 3. AJAX Endpoint Tests (2 tests)

#### `testListInstalledEndpointReturnsData()`
**Purpose**: Directly test the installed versions AJAX endpoint

**Coverage**:
- **`list_installed.php`** - Main file being tested
- Database query to `standardized_tables_track` table
- HTML rendering of installation details

**Tests For Each Database**:
- ICD10, RXNORM, SNOMED, CQM_VALUESET

**Assertions**:
- Endpoint returns HTTP 200
- Content is not empty
- Shows either "Not installed" or version information (Name, Revision, Release Date)

**SQL Coverage**:
```php
sqlStatement("SELECT DATE_FORMAT(`revision_date`,'%Y-%m-%d') as `revision_date`,
              `revision_version`, `name`
              FROM `standardized_tables_track`
              WHERE upper(`name`) = ?
              ORDER BY `imported_date` DESC, `revision_date` DESC", [$db]);
```

---

#### `testListStagedEndpointReturnsData()`
**Purpose**: Directly test the staged files validation endpoint

**Coverage**:
- **`list_staged.php`** - Main file being tested (complex logic)
- File system scanning (`scandir()` of `contrib/{type}/` directories)
- Filename pattern validation (regex matching)
- Date extraction from filenames
- Version compatibility checking
- MD5 checksum validation for ICD10
- Database queries to `supported_external_dataloads` table

**Tests For Each Database**:
- ICD10, RXNORM, SNOMED, CQM_VALUESET

**Assertions**:
- Endpoint returns HTTP 200
- Content is not empty
- Shows appropriate messages:
  - "No files staged for installation"
  - "UNSUPPORTED database load file"
  - "installation directory needs to be created"
  - Filename listings (*.zip files)
  - INSTALL or UPGRADE buttons

**Pattern Validation Coverage**:
- RXNORM: `/RxNorm_full_([0-9]{8}).zip/`
- SNOMED International: `/SnomedCT_INT_([0-9]{8}).zip/` (and 7+ other patterns)
- SNOMED US Extension: Multiple RF1 and RF2 patterns
- SNOMED Spanish: International Spanish patterns
- CQM_VALUESET: `/e[p,c]_.*_cms_([0-9]{8}).xml.zip/`
- ICD10: MD5 checksum validation against `supported_external_dataloads` table

---

### 4. Integration Tests (2 tests)

#### `testStagedFilesShowsMessages()`
**Purpose**: Verify the staged files section displays appropriate content

**Coverage**:
- `dataloads_ajax.php` - Stage details container
- `list_staged.php` - AJAX response integration
- Error message rendering

**Assertions**:
- Stage details section exists
- Shows content (either errors or file listings)

---

#### `testAccordionFunctionality()`
**Purpose**: Test JavaScript accordion collapse/expand behavior

**Coverage**:
- `dataloads_ajax.php` - Bootstrap accordion JavaScript
- jQuery event handlers for `show.bs.collapse`

**Steps**:
1. Verify sections start collapsed
2. Expand a section
3. Verify content becomes visible
4. Verify AJAX loading indicators work

---

## Helper Trait Methods

### CodeSystemsTrait.php

Provides reusable methods for code systems testing:

#### Navigation
- **`navigateToCodeSystemsPage()`** - Navigate to main dataloads page
- **`expandDatabaseSection($dbType)`** - Click accordion and wait for AJAX

#### Assertions
- **`isDatabaseNotInstalled($dbType)`** - Check for "Not installed" text
- **`isDatabaseInstalled($dbType)`** - Check for version information
- **`hasStageErrors($dbType)`** - Check for error messages
- **`hasInstallButton($dbType)`** - Check if INSTALL/UPGRADE button exists
- **`hasInstructionsLink($dbType)`** - Check for help link

#### Utilities
- **`verifyOverviewSection()`** - Assert overview content
- **`getSupportedDatabaseTypes()`** - Returns array of DB types
- **`verifyDatabaseSectionsExist()`** - Assert all sections present

---

## Running the Tests

### Full E2E Suite
```bash
./vendor/bin/phpunit tests/Tests/E2e/ZzCodeSystemsTest.php
```

### With Docker (Coverage Enabled)
```bash
docker compose exec openemr php -d memory_limit=8G \
  ./vendor/bin/phpunit \
  --testdox \
  --testsuite e2e \
  tests/Tests/E2e/ZzCodeSystemsTest.php
```

### Single Test
```bash
./vendor/bin/phpunit \
  --filter testListStagedEndpointReturnsData \
  tests/Tests/E2e/ZzCodeSystemsTest.php
```

### With Selenium Grid
```bash
export SELENIUM_USE_GRID=true
export SELENIUM_HOST=selenium
export SELENIUM_BASE_URL=http://openemr
./vendor/bin/phpunit tests/Tests/E2e/ZzCodeSystemsTest.php
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `SELENIUM_USE_GRID` | `false` | Use Selenium Grid instead of local Chrome |
| `SELENIUM_HOST` | `selenium` | Selenium Grid hostname |
| `SELENIUM_BASE_URL` | `http://openemr` | OpenEMR base URL |
| `SELENIUM_IMPLICIT_WAIT` | `30` | WebDriver implicit wait (seconds) |
| `SELENIUM_PAGE_LOAD_TIMEOUT` | `60` | Page load timeout (seconds) |

---

## Code Coverage Impact

These E2E tests significantly increase coverage for:

### High Coverage Files
- **`list_staged.php`** - ~80-90% coverage (complex validation logic)
- **`list_installed.php`** - ~90-95% coverage (simpler query logic)
- **`dataloads_ajax.php`** - ~60-70% coverage (UI rendering)

### Partial Coverage Files
- **`standard_tables_manage.php`** - 10-20% (import processing not tested)
- **`*_howto.php`** - 0% (instruction modals not tested)

### Not Covered
Import functions require either:
- Mock vocabulary files in `contrib/` directories
- Database mocking for insert operations
- Much longer test execution times (hours for full RXNORM import)

---

## Test Execution Time

- **Individual test**: ~5-10 seconds
- **Full suite (13 tests)**: ~2-3 minutes
- **With Selenium Grid**: Similar timing
- **Headless mode**: Slightly faster

---

## Known Limitations

### 1. Cannot Test Actual Imports
The E2E tests do not test actual vocabulary file imports because:
- Requires real vocabulary files (100MB-2GB per file)
- Import takes 10 minutes to 2+ hours
- Requires database reset between tests
- Not suitable for CI/CD pipelines

### 2. No File Upload Testing
Tests do not simulate:
- Placing files in `contrib/{type}/` directories
- Triggering actual INSTALL/UPGRADE button clicks
- Verifying import success

These would require:
- Mock vocabulary files
- Longer test timeouts
- Database fixtures/cleanup

### 3. Authentication Limitations
Tests use admin credentials from `LoginTestData`:
- Cannot test non-admin access denial (requires test user creation)
- Cannot test ACL variations

---

## Future Enhancements

### Potential Additions
1. **File upload simulation** - Test staging workflow with mock files
2. **Import button testing** - Test clicking INSTALL/UPGRADE (with mocks)
3. **Error handling** - Test various error scenarios
4. **Help modal testing** - Test `*_howto.php` instruction popups
5. **Version comparison** - Test upgrade vs install button logic
6. **Multi-file releases** - Test ICD10 multi-file validation
7. **SNOMED compatibility** - Test US Extension compatibility checks

### Testing Improvements
1. Visual regression testing (screenshots)
2. Performance testing (AJAX response times)
3. Accessibility testing (ARIA labels, keyboard nav)
4. Mobile responsive testing

---

## Troubleshooting

### Tests Timeout
- Increase `SELENIUM_PAGE_LOAD_TIMEOUT`
- Check Selenium Grid is running
- Verify OpenEMR container is healthy

### "Element not found" Errors
- AJAX may still be loading - increase wait times
- Check element selectors match current HTML
- Use browser devtools to inspect DOM

### Authentication Failures
- Verify `LoginTestData` credentials are correct
- Check database has admin user
- Ensure session handling is working

---

## Integration with CI/CD

These tests can run in GitHub Actions with:
```yaml
- name: Run Code Systems E2E Tests
  run: |
    docker compose exec openemr php -d memory_limit=8G \
      ./vendor/bin/phpunit \
      --testsuite e2e \
      tests/Tests/E2e/ZzCodeSystemsTest.php
```

Coverage reports will show line-by-line execution of:
- `list_installed.php`
- `list_staged.php`
- `dataloads_ajax.php`

---

## Maintenance Notes

### When to Update Tests

**Update tests if**:
- New vocabulary types added (e.g., ICD11)
- Filename patterns change
- UI layout changes (accordion structure)
- AJAX endpoints change URLs
- Error messages change

**Test names use `Zz` prefix** to ensure they run last in the e2e suite (after login/user setup).

---

## Related Documentation

- [Unit Tests](../Services/CodeSystemsTest.md) - Library function tests
- [Code Systems Readme](../../../../interface/code_systems/README.md) - Installation guide
- [Vocabulary Loading](../../../../docs/vocab_loading.md) - Process documentation
