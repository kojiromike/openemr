#!/usr/bin/env php
<?php

/**
 * CLI tool for importing medical code vocabularies
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
 * Display usage information
 */
function showUsage(): void
{
    echo <<<USAGE
Usage: php import_codes_cli.php [options] <code-type> <file-path>

Code Types:
  RXCUI          - RxNorm Concept Unique Identifiers (prescribable drugs)
  RXNORM         - Full RxNorm database
  SNOMED         - SNOMED-CT (RF1 or RF2 format)
  ICD10          - ICD-10 diagnosis and procedure codes
  CQM_VALUESET   - Clinical Quality Measure valuesets

Options:
  --help, -h           Show this help message
  --replace            Delete existing codes before import (default for RXCUI)
  --no-replace         Do not delete existing codes (update/insert only)
  --old-method         Use legacy import method (for testing)
  --batch-size=N       Set batch size (default: 1000, max: 5000)
  --validate-only      Validate file without importing
  --validate-after     Validate results after import
  --format=FORMAT      SNOMED format: RF1 or RF2 (default: RF2)
  --temp-dir=PATH      Temporary directory for extraction
  --is-windows         Set for Windows environment (RXNORM)
  --verbose, -v        Verbose output

Examples:
  # Import RXCUI from zip file
  php import_codes_cli.php RXCUI /path/to/RxNorm_prescribable.zip

  # Import SNOMED RF2 with validation
  php import_codes_cli.php --validate-after --format=RF2 SNOMED /path/to/snomed/

  # Import ICD-10 without replacing existing codes
  php import_codes_cli.php --no-replace ICD10 /path/to/icd10/

  # Validate file only
  php import_codes_cli.php --validate-only RXCUI /path/to/file.zip

  # Compare old vs new method
  php import_codes_cli.php --old-method RXCUI /path/to/file.zip

USAGE;
}

/**
 * Parse command line arguments
 */
function parseArgs(array $argv): array
{
    $options = [
        'replace' => null,
        'old_method' => false,
        'batch_size' => 1000,
        'validate_only' => false,
        'validate_after' => false,
        'format' => 'RF2',
        'temp_dir' => null,
        'is_windows' => false,
        'verbose' => false,
        'code_type' => null,
        'file_path' => null,
    ];

    $positional = [];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if ($arg === '--help' || $arg === '-h') {
            showUsage();
            exit(0);
        } elseif ($arg === '--replace') {
            $options['replace'] = true;
        } elseif ($arg === '--no-replace') {
            $options['replace'] = false;
        } elseif ($arg === '--old-method') {
            $options['old_method'] = true;
        } elseif ($arg === '--validate-only') {
            $options['validate_only'] = true;
        } elseif ($arg === '--validate-after') {
            $options['validate_after'] = true;
        } elseif ($arg === '--is-windows') {
            $options['is_windows'] = true;
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        } elseif (str_starts_with((string) $arg, '--batch-size=')) {
            $options['batch_size'] = (int)substr((string) $arg, 13);
        } elseif (str_starts_with((string) $arg, '--format=')) {
            $options['format'] = strtoupper(substr((string) $arg, 9));
        } elseif (str_starts_with((string) $arg, '--temp-dir=')) {
            $options['temp_dir'] = substr((string) $arg, 11);
        } else {
            $positional[] = $arg;
        }
    }

    if (count($positional) < 2) {
        echo "Error: Missing required arguments\n\n";
        showUsage();
        exit(1);
    }

    $options['code_type'] = strtoupper((string) $positional[0]);
    $options['file_path'] = $positional[1];

    // Set default replace behavior
    if ($options['replace'] === null) {
        $options['replace'] = ($options['code_type'] === 'RXCUI');
    }

    return $options;
}

/**
 * Main execution
 */
function main(array $argv): void
{
    $options = parseArgs($argv);

    $verbose = $options['verbose'];

    if ($verbose) {
        echo "Code Import CLI Tool\n";
        echo "====================\n\n";
        echo "Code Type: {$options['code_type']}\n";
        echo "File Path: {$options['file_path']}\n";
        echo "Replace: " . ($options['replace'] ? 'yes' : 'no') . "\n";
        echo "Old Method: " . ($options['old_method'] ? 'yes' : 'no') . "\n";
        echo "Batch Size: {$options['batch_size']}\n\n";
    }

    // Create service
    $service = new CodeImportService(
        $options['old_method'],
        $options['batch_size']
    );

    // Validate file
    if ($verbose) {
        echo "Validating file...\n";
    }

    if (!file_exists($options['file_path'])) {
        echo "Error: File not found: {$options['file_path']}\n";
        exit(1);
    }

    $loaderOptions = [
        'format' => $options['format'],
        'is_windows' => $options['is_windows'],
    ];

    if (!$service->validateFile($options['code_type'], $options['file_path'], $loaderOptions)) {
        echo "Error: Invalid file format for {$options['code_type']}\n";
        exit(1);
    }

    if ($verbose) {
        echo "File validation passed\n\n";
    }

    if ($options['validate_only']) {
        echo "Validation successful. Exiting without import.\n";
        exit(0);
    }

    // Estimate row count
    if ($verbose) {
        echo "Estimating row count...\n";
        $estimated = $service->estimateRowCount($options['code_type'], $options['file_path'], $loaderOptions);
        echo "Estimated rows: " . number_format($estimated) . "\n\n";
    }

    // Perform import
    echo "Starting import...\n";
    $startTime = microtime(true);

    try {
        $importOptions = [
            'replace' => $options['replace'],
            'format' => $options['format'],
            'is_windows' => $options['is_windows'],
        ];

        if ($options['temp_dir']) {
            $importOptions['temp_dir'] = $options['temp_dir'];
        }

        $stats = $service->import($options['code_type'], $options['file_path'], $importOptions);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        echo "\nImport completed successfully!\n";
        echo "Duration: {$duration} seconds\n";
        echo "\nStatistics:\n";
        foreach ($stats as $key => $value) {
            echo "  " . ucwords(str_replace('_', ' ', $key)) . ": " . number_format($value) . "\n";
        }

        // Validate after import if requested
        if ($options['validate_after']) {
            echo "\nValidating import results...\n";
            $validator = new ImportValidator();

            $validationResults = match ($options['code_type']) {
                'RXCUI' => $validator->validateRxcuiImport($GLOBALS['code_types']['RXCUI']['id']),
                'RXNORM' => $validator->validateRxnormImport(),
                'SNOMED' => $validator->validateSnomedImport($options['format']),
                'ICD10' => $validator->validateIcd10Import(),
                'CQM_VALUESET' => $validator->validateCqmValuesetImport(),
                default => ['validation_passed' => false, 'errors' => ['Unknown code type']],
            };

            if ($validationResults['validation_passed']) {
                echo "Validation passed!\n";
            } else {
                echo "Validation failed:\n";
                foreach ($validationResults['errors'] as $error) {
                    echo "  - $error\n";
                }
            }

            if ($verbose) {
                echo "\nDetailed validation results:\n";
                print_r($validationResults);
            }
        }
    } catch (\Exception $e) {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        echo "\nImport failed after {$duration} seconds\n";
        echo "Error: " . $e->getMessage() . "\n";

        if ($verbose) {
            echo "\nStack trace:\n";
            echo $e->getTraceAsString() . "\n";
        }

        exit(1);
    }
}

// Run main
main($argv);
