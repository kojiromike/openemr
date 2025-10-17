#!/usr/bin/env php
<?php

/**
 * Merge E2E coverage files collected during web requests.
 * This processes the raw Xdebug data files and creates a single coverage report.
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

define('WEBROOT', dirname(__DIR__));
require_once WEBROOT . '/vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;
use SebastianBergmann\CodeCoverage\Report\Clover;

$coverageDir = '/tmp/openemr-coverage/e2e';
$outputDir = WEBROOT . '/coverage';

if (!is_dir($coverageDir)) {
    die("Coverage directory does not exist: $coverageDir\n");
}

// Find all raw coverage files
$files = glob($coverageDir . '/coverage.e2e.*.raw.php');
if (empty($files)) {
    die("No coverage files found in $coverageDir\n");
}

echo "Found " . count($files) . " coverage files\n";

// Create a CodeCoverage object
$codeCoverageFilter = new Filter();
$codeCoverageFilter->includeDirectory(WEBROOT . '/src');
$codeCoverageFilter->includeDirectory(WEBROOT . '/interface');
$codeCoverageFilter->includeDirectory(WEBROOT . '/library');

$codeCoverageDriver = new XdebugDriver($codeCoverageFilter);
$codeCoverage = new CodeCoverage($codeCoverageDriver, $codeCoverageFilter);

// Disable static analysis caching to save memory
// We'll process files one at a time
echo "Processing coverage files...\n";
$processed = 0;

foreach ($files as $file) {
    try {
        $coverage = include $file;
        if (!is_array($coverage)) {
            echo "Skipping invalid file: $file\n";
            continue;
        }

        // Convert raw xdebug array to RawCodeCoverageData and append it
        $rawData = RawCodeCoverageData::fromXdebugWithoutPathCoverage($coverage);
        $codeCoverage->append($rawData, 'e2e-test-' . $processed);

        $processed++;
        if ($processed % 10 === 0) {
            echo "Processed $processed files...\n";
        }
    } catch (Throwable $e) {
        echo "Error processing $file: " . $e->getMessage() . "\n";
    }
}

echo "Successfully processed $processed coverage files\n";

// Create output directory
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Generate HTML report
echo "Generating HTML report...\n";
$htmlReport = new HtmlReport();
$htmlReport->process($codeCoverage, $outputDir . '/html');

// Generate Clover XML report
echo "Generating Clover XML report...\n";
$clover = new Clover();
$clover->process($codeCoverage, $outputDir . '/clover.xml');

// Save serialized coverage for later use
echo "Saving serialized coverage...\n";
$phpReport = new PHP();
$phpReport->process($codeCoverage, $outputDir . '/coverage.cov');

echo "\nCoverage reports generated:\n";
echo "  HTML: $outputDir/html/index.html\n";
echo "  Clover XML: $outputDir/clover.xml\n";
echo "  Serialized: $outputDir/coverage.cov\n";
