#!/usr/bin/env php
<?php

/**
 * Comparison script to validate new import methods against old methods
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// Set up environment
$ignoreAuth = true;
require_once(__DIR__ . '/../../../../interface/globals.php');

use OpenEMR\Services\CodeImport\CodeImportService;
use OpenEMR\Services\CodeImport\ImportValidator;

/**
 * Display usage
 */
function showUsage(): void
{
    echo <<<USAGE
Usage: php compare_methods.php <code-type> <file-path> [options]

This script runs both old and new import methods and compares the results.

Code Types:
  RXCUI, RXNORM, SNOMED, ICD10, CQM_VALUESET

Options:
  --batch-size=N       Batch size for new method (default: 1000)
  --no-cleanup         Don't cleanup between runs
  --format=FORMAT      SNOMED format (RF1 or RF2)
  --verbose, -v        Verbose output

Example:
  php compare_methods.php RXCUI /path/to/file.zip --verbose

USAGE;
}

/**
 * Parse arguments
 */
function parseArgs(array $argv): array
{
    if (count($argv) < 3) {
        showUsage();
        exit(1);
    }

    $options = [
        'code_type' => strtoupper((string) $argv[1]),
        'file_path' => $argv[2],
        'batch_size' => 1000,
        'cleanup' => true,
        'format' => 'RF2',
        'verbose' => false,
    ];

    for ($i = 3; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if (str_starts_with((string) $arg, '--batch-size=')) {
            $options['batch_size'] = (int)substr((string) $arg, 13);
        } elseif ($arg === '--no-cleanup') {
            $options['cleanup'] = false;
        } elseif (str_starts_with((string) $arg, '--format=')) {
            $options['format'] = strtoupper(substr((string) $arg, 9));
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        }
    }

    return $options;
}

/**
 * Get table name for code type
 */
function getTableForCodeType(string $codeType): string
{
    return match ($codeType) {
        'RXCUI' => 'codes',
        'RXNORM' => 'rxnconso',
        'SNOMED' => 'sct2_description',
        'ICD10' => 'icd10_dx_order_code',
        'CQM_VALUESET' => 'valueset',
        default => 'codes',
    };
}

/**
 * Backup table
 */
function backupTable(string $table): void
{
    $backupTable = $table . '_backup_' . time();
    sqlStatement("CREATE TABLE `$backupTable` LIKE `$table`");
    sqlStatement("INSERT INTO `$backupTable` SELECT * FROM `$table`");
    echo "  Backed up $table to $backupTable\n";
}

/**
 * Restore table
 */
function restoreTable(string $table, string $backupTable): void
{
    sqlStatement("TRUNCATE TABLE `$table`");
    sqlStatement("INSERT INTO `$table` SELECT * FROM `$backupTable`");
    sqlStatement("DROP TABLE `$backupTable`");
    echo "  Restored $table from $backupTable\n";
}

/**
 * Truncate table for code type
 */
function truncateForCodeType(string $codeType): void
{
    global $code_types;

    switch ($codeType) {
        case 'RXCUI':
            $codeTypeId = $code_types['RXCUI']['id'];
            sqlStatement("DELETE FROM codes WHERE code_type = ?", [$codeTypeId]);
            break;
        case 'RXNORM':
            $tables = ['rxnatomarchive', 'rxnconso', 'rxncui', 'rxncuichanges', 'rxndoc', 'rxnrel', 'rxnsab', 'rxnsat', 'rxnsty'];
            foreach ($tables as $table) {
                sqlStatement("TRUNCATE TABLE `$table`");
            }
            break;
        case 'SNOMED':
            $tables = ['sct2_concept', 'sct2_description', 'sct2_identifier', 'sct2_relationship', 'sct2_statedrelationship', 'sct2_textdefinition'];
            foreach ($tables as $table) {
                sqlStatement("TRUNCATE TABLE `$table`");
            }
            break;
        case 'ICD10':
            sqlStatement("TRUNCATE TABLE icd10_dx_order_code");
            sqlStatement("TRUNCATE TABLE icd10_pcs_order_code");
            break;
        case 'CQM_VALUESET':
            sqlStatement("TRUNCATE TABLE valueset");
            sqlStatement("TRUNCATE TABLE valueset_oid");
            break;
    }
}

/**
 * Main comparison function
 */
function runComparison(array $options): void
{
    $codeType = $options['code_type'];
    $filePath = $options['file_path'];
    $verbose = $options['verbose'];

    echo "\n================================\n";
    echo "Code Import Method Comparison\n";
    echo "================================\n\n";
    echo "Code Type: $codeType\n";
    echo "File: $filePath\n";
    echo "Batch Size: {$options['batch_size']}\n\n";

    $validator = new ImportValidator();
    $service = new CodeImportService(false, $options['batch_size']);

    // Validate file
    echo "Validating file...\n";
    if (!file_exists($filePath)) {
        echo "Error: File not found: $filePath\n";
        exit(1);
    }

    $loaderOptions = ['format' => $options['format']];
    if (!$service->validateFile($codeType, $filePath, $loaderOptions)) {
        echo "Error: Invalid file format\n";
        exit(1);
    }
    echo "  File validation passed\n\n";

    // Get main table for comparison
    $table = getTableForCodeType($codeType);

    // Run OLD method
    echo "========================================\n";
    echo "Running OLD method...\n";
    echo "========================================\n";

    truncateForCodeType($codeType);

    $service->setUseOldMethod(true);
    $startTime = microtime(true);

    try {
        $importOptions = array_merge($loaderOptions, ['replace' => true]);
        $oldStats = $service->import($codeType, $filePath, $importOptions);
    } catch (\Exception $e) {
        echo "Error in old method: " . $e->getMessage() . "\n";
        exit(1);
    }

    $oldDuration = round(microtime(true) - $startTime, 2);

    echo "\nOld method completed in {$oldDuration} seconds\n";
    if ($verbose) {
        echo "Statistics:\n";
        print_r($oldStats);
    }

    // Create snapshot
    echo "Creating snapshot of old method results...\n";
    $oldSnapshot = $validator->createSnapshot($table);

    // Run NEW method
    echo "\n========================================\n";
    echo "Running NEW method...\n";
    echo "========================================\n";

    truncateForCodeType($codeType);

    $service->setUseOldMethod(false);
    $startTime = microtime(true);

    try {
        $importOptions = array_merge($loaderOptions, ['replace' => true]);
        $newStats = $service->import($codeType, $filePath, $importOptions);
    } catch (\Exception $e) {
        echo "Error in new method: " . $e->getMessage() . "\n";
        exit(1);
    }

    $newDuration = round(microtime(true) - $startTime, 2);

    echo "\nNew method completed in {$newDuration} seconds\n";
    if ($verbose) {
        echo "Statistics:\n";
        print_r($newStats);
    }

    // Create snapshot
    echo "Creating snapshot of new method results...\n";
    $newSnapshot = $validator->createSnapshot($table);

    // Compare results
    echo "\n========================================\n";
    echo "Comparison Results\n";
    echo "========================================\n\n";

    echo "Performance:\n";
    echo "  Old method: {$oldDuration} seconds\n";
    echo "  New method: {$newDuration} seconds\n";
    $speedup = round($oldDuration / $newDuration, 2);
    echo "  Speedup: {$speedup}x\n\n";

    echo "Data Comparison:\n";
    $comparison = $validator->compareSnapshots($table, $oldSnapshot, $newSnapshot);

    echo "  Row count (old): " . number_format($oldSnapshot['row_count']) . "\n";
    echo "  Row count (new): " . number_format($newSnapshot['row_count']) . "\n";
    echo "  Difference: " . number_format($comparison['row_count_diff']) . "\n";
    echo "  Checksum match: " . ($comparison['checksum_before'] === $comparison['checksum_after'] ? 'YES' : 'NO') . "\n";
    echo "  Data match: " . ($comparison['matches'] ? 'YES' : 'NO') . "\n\n";

    if (!$comparison['matches']) {
        echo "WARNING: Results do not match!\n";
        echo "  Old checksum: {$comparison['checksum_before']}\n";
        echo "  New checksum: {$comparison['checksum_after']}\n\n";
    } else {
        echo "SUCCESS: Results match!\n\n";
    }

    // Detailed validation
    echo "Detailed Validation:\n";
    $validationResults = match ($codeType) {
        'RXCUI' => $validator->validateRxcuiImport($GLOBALS['code_types']['RXCUI']['id']),
        'RXNORM' => $validator->validateRxnormImport(),
        'SNOMED' => $validator->validateSnomedImport($options['format']),
        'ICD10' => $validator->validateIcd10Import(),
        'CQM_VALUESET' => $validator->validateCqmValuesetImport(),
        default => ['validation_passed' => false],
    };

    if ($validationResults['validation_passed']) {
        echo "  Validation: PASSED\n";
    } else {
        echo "  Validation: FAILED\n";
        if (!empty($validationResults['errors'])) {
            echo "  Errors:\n";
            foreach ($validationResults['errors'] as $error) {
                echo "    - $error\n";
            }
        }
    }

    echo "\n========================================\n";
    echo "Summary\n";
    echo "========================================\n\n";
    echo "Old method: {$oldDuration}s\n";
    echo "New method: {$newDuration}s\n";
    echo "Speedup: {$speedup}x faster\n";
    echo "Data integrity: " . ($comparison['matches'] ? 'VERIFIED' : 'FAILED') . "\n";
    echo "\n";
}

// Run comparison
$options = parseArgs($argv);
runComparison($options);
