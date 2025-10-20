<?php

/**
 * Converts raw E2E coverage PHP files to PHPUnit .cov format.
 * This script loads all the raw xdebug coverage arrays and merges them
 * into a proper SebastianBergmann\CodeCoverage\CodeCoverage object.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;

$coverageDir = '/tmp/openemr-coverage/e2e';
$outputFile = '/tmp/openemr-coverage/coverage.e2e.cov';

echo "Converting raw E2E coverage files...\n";

// Find all raw coverage PHP files
$files = glob($coverageDir . '/*.php');
if (empty($files)) {
    echo "Error: No coverage files found in {$coverageDir}\n";
    exit(1);
}

echo "Found " . count($files) . " coverage file(s) to process\n";

// Create a new CodeCoverage object
$filter = new Filter();
$coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);

// Load and merge each raw coverage file
$processedCount = 0;
foreach ($files as $file) {
    try {
        // Load the raw xdebug coverage array
        $rawCoverage = require $file;

        if (!is_array($rawCoverage)) {
            echo "Warning: {$file} did not return an array, skipping\n";
            continue;
        }

        // Wrap in RawCodeCoverageData object
        $rawData = RawCodeCoverageData::fromXdebugWithoutPathCoverage($rawCoverage);

        // Merge the raw data into the coverage object
        $coverage->append($rawData, basename($file));
        $processedCount++;

        if ($processedCount % 100 === 0) {
            echo "Processed {$processedCount}/" . count($files) . " files...\n";
        }
    } catch (Exception $e) {
        echo "Warning: Failed to process {$file}: {$e->getMessage()}\n";
    }
}

echo "Successfully processed {$processedCount} coverage files\n";

// Save the merged coverage object
echo "Saving merged coverage to {$outputFile}...\n";
file_put_contents($outputFile, serialize($coverage));

// Generate clover report
$cloverFile = '/tmp/openemr-coverage/coverage.e2e.clover.xml';
echo "Generating clover report to {$cloverFile}...\n";
$cloverWriter = new SebastianBergmann\CodeCoverage\Report\Clover();
$cloverWriter->process($coverage, $cloverFile);

echo "Conversion complete!\n";
