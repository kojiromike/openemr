# Code Import Service

This directory contains the optimized code import service for loading medical vocabularies into OpenEMR.

## Overview

The Code Import Service provides a well-architected, performant alternative to the legacy code loading methods. It uses batched SQL operations and optimized transaction handling to dramatically speed up imports while maintaining compatibility with existing data.

## Features

- **Batched Operations**: Uses bulk inserts (1000-5000 rows per batch) instead of row-by-row inserts
- **Idempotent Imports**: Supports ON DUPLICATE KEY UPDATE for safe re-imports
- **Memory Efficient**: Streams large files using generators
- **Backward Compatible**: Can fall back to old methods for validation
- **Extensible**: Easy to add new code type loaders

## Architecture

### Class Structure

```
CodeLoaderInterface          - Interface for all loaders
    ↑
AbstractCodeLoader          - Base class with common functionality
    ↑
    ├── RxcuiLoader         - RXCUI codes (native)
    ├── RxnormLoader        - RXNORM full tables
    ├── SnomedLoader        - SNOMED-CT (RF1/RF2)
    ├── Icd10Loader         - ICD-10 DX/PCS
    └── CqmValuesetLoader   - CQM valuesets

CodeImportService           - Main service coordinator
ImportValidator            - Validation and comparison tools
```

### Key Classes

#### CodeImportService
Main entry point for code imports. Manages loaders and provides a unified interface.

```php
$service = new CodeImportService();
$stats = $service->import('RXCUI', '/path/to/file.zip', [
    'replace' => true,
]);
```

#### AbstractCodeLoader
Provides common functionality for all loaders:
- Batched insert/upsert operations
- Transaction management
- File streaming with generators
- Event dispatching

#### Individual Loaders
Each loader implements the `CodeLoaderInterface`:
- `import()` - Import codes from file
- `validate()` - Validate file format
- `estimateRowCount()` - Estimate import size
- `getCodeType()` - Return code type identifier

## Supported Code Types

### RXCUI (Native)
Loads prescribable drug codes from RXNCONSO.RRF file.

**File**: RXNCONSO.RRF (or zip containing it)
**Format**: Pipe-delimited RRF
**Filter**: CVF=4096, SAB=RXNORM
**Optimization**: Significantly faster with batched inserts

### RXNORM (External)
Loads full RXNORM database tables.

**Files**: Multiple RRF files (RXNCONSO, RXNREL, etc.)
**Format**: Pipe-delimited RRF
**Optimization**: Uses LOAD DATA INFILE for maximum speed

### SNOMED
Loads SNOMED-CT concepts and descriptions.

**Formats**: RF1 or RF2
**Files**: Tab-delimited text files
**Optimization**: Batched LOAD DATA INFILE

### ICD-10
Loads ICD-10 diagnosis and procedure codes.

**Files**: Fixed-width text files
**Format**: CMS-provided format
**Optimization**: Batched inserts with field extraction

### CQM Valuesets
Loads Clinical Quality Measure valuesets.

**Format**: XML
**Optimization**: Batched upserts with ON DUPLICATE KEY UPDATE

## Performance

The optimized loaders use batched SQL operations to significantly improve import performance compared to legacy row-by-row methods.

**Expected performance improvements:**
- Batched inserts reduce database round trips by 1000x
- Streaming file processing minimizes memory usage
- Optimized transaction handling reduces overhead
- `LOAD DATA INFILE` provides maximum MySQL bulk loading speed

**Actual speedup will vary** based on hardware, MySQL configuration, dataset size, and system load.

To benchmark on your system:
```bash
php compare_methods.php RXCUI /path/to/file.zip --verbose
```

## Usage

### Basic Import

```php
use OpenEMR\Services\CodeImport\CodeImportService;

$service = new CodeImportService();

// Import RXCUI codes
$stats = $service->import('RXCUI', '/path/to/RxNorm.zip', [
    'replace' => true,  // Replace all existing codes
]);

echo "Inserted: {$stats['inserted']}, Updated: {$stats['updated']}";
```

### Advanced Options

```php
// Use old method for comparison
$service = new CodeImportService(useOldMethod: true);

// Adjust batch size
$service->setBatchSize(2000);

// Import SNOMED RF2
$stats = $service->import('SNOMED', '/path/to/snomed/', [
    'format' => 'RF2',
    'temp_dir' => '/tmp/SNOMED',
]);
```

### Validation

```php
use OpenEMR\Services\CodeImport\ImportValidator;

$validator = new ImportValidator();

// Validate import results
$results = $validator->validateRxcuiImport($codeTypeId);

if (!$results['validation_passed']) {
    foreach ($results['errors'] as $error) {
        echo "Error: $error\n";
    }
}
```

### Comparison Testing

```php
// Create snapshots before/after
$beforeSnapshot = $validator->createSnapshot('codes');

// Run import with new method
$service->import('RXCUI', $filePath, ['replace' => true]);

$afterNewSnapshot = $validator->createSnapshot('codes');

// Run import with old method
$service->setUseOldMethod(true);
$service->import('RXCUI', $filePath, ['replace' => true]);

$afterOldSnapshot = $validator->createSnapshot('codes');

// Compare results
$comparison = $validator->compareSnapshots('codes', $afterNewSnapshot, $afterOldSnapshot);
```

## Configuration

### Global Configuration

Add to `globals.php` or configuration:

```php
// Use optimized import methods (default: true)
$GLOBALS['code_import_use_optimized'] = true;

// Use old import methods for compatibility (default: false)
$GLOBALS['code_import_use_old_method'] = false;

// Batch size for imports (default: 1000)
$GLOBALS['code_import_batch_size'] = 1000;
```

### Per-Import Configuration

```php
$stats = $service->import('RXCUI', $filePath, [
    'replace' => true,           // Delete all codes before import
    'batch_size' => 2000,        // Override batch size
    'old_method' => false,       // Use old method
]);
```

## Integration with Legacy Code

The service is designed to be backward compatible:

1. **load_codes.php**: Refactored to use RxcuiLoader
2. **standard_tables_capture.inc.php**: Can call new loaders or old functions
3. **Toggle Support**: Can switch between old/new methods via config

## Testing

### Unit Testing

```bash
php vendor/bin/phpunit tests/Services/CodeImport/
```

### Manual Testing

```php
// Test with old and new methods
$service = new CodeImportService();

// New method
$service->setUseOldMethod(false);
$statsNew = $service->import('RXCUI', $file, ['replace' => true]);

// Old method
$service->setUseOldMethod(true);
$statsOld = $service->import('RXCUI', $file, ['replace' => true]);

// Compare
$validator = new ImportValidator();
$report = $validator->generateComparisonReport($statsOld, $statsNew);
```

## Extending

To add a new code type:

1. Create a loader class extending `AbstractCodeLoader`
2. Implement required interface methods
3. Add to `CodeImportService::getLoader()`
4. Add to `getSupportedCodeTypes()`

Example:

```php
class MyCodeLoader extends AbstractCodeLoader
{
    public function getCodeType(): string
    {
        return 'MYCODE';
    }

    public function import(string $filePath, array $options = []): array
    {
        // Implementation
    }
}
```

## Troubleshooting

### Import Fails

1. Check file permissions
2. Verify MySQL `local_infile` is enabled
3. Check `max_allowed_packet` size
4. Verify temp directory is writable

### Memory Issues

1. Reduce batch size: `setBatchSize(500)`
2. Increase PHP memory_limit
3. Check for file streaming (should not load entire file into memory)

### Performance Issues

1. Ensure indexes are created after import, not before
2. Disable foreign key checks during import
3. Use batched operations, not row-by-row
4. Monitor MySQL slow query log

## Best Practices

1. **Always validate** files before import
2. **Use transactions** for atomic imports
3. **Create indexes** after bulk inserts
4. **Test with old method** first for new code types
5. **Monitor performance** and adjust batch sizes
6. **Clean up temp files** after import

## License

GNU General Public License 3 - See LICENSE file
